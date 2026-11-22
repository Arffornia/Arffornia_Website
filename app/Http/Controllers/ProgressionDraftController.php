<?php

namespace App\Http\Controllers;

use App\Models\ProgressionDraft;
use App\Models\User;
use App\Progression\Editor\DraftActionRefused;
use App\Progression\Editor\DraftConflict;
use App\Progression\Editor\DraftEditor;
use App\Progression\Editor\DraftHistory;
use App\Progression\Editor\DraftPublisher;
use App\Progression\Editor\PublishRefused;
use App\Progression\Finding;
use App\Models\ProgressionDraftEntry;
use App\Progression\Editor\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/** Spec 019: the admin editor's draft. Admin only (routes/api.php). */
class ProgressionDraftController extends Controller
{
    public function __construct(
        private readonly DraftService $drafts,
        private readonly DraftHistory $history,
        private readonly DraftPublisher $publisher,
    ) {
    }

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate(['from_version' => 'sometimes|nullable|integer|min:1']);

        return $this->guard(function () use ($request, $validated) {
            [$draft, $created] = $this->drafts->openOrCreate(self::userId($request), $validated['from_version'] ?? null);

            return response()->json($this->payload($draft), $created ? Response::HTTP_CREATED : Response::HTTP_OK);
        });
    }

    public function show(ProgressionDraft $draft): JsonResponse
    {
        return response()->json($this->payload($draft));
    }

    public function act(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $request->validate([
            'revision' => 'required|integer|min:0',
            'action' => 'required|string|in:' . implode(',', DraftEditor::ACTIONS),
            'payload' => 'present|array',
        ]);
        $action = $request->input('action');
        // The raw body, not the input bag: the global middleware would trim texts and turn "" into null.
        $raw = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['payload'] ?? [];
        $payload = Validator::make(is_array($raw) ? $raw : [], DraftEditor::rules($action))->validate();

        return $this->guard(function () use ($request, $draft, $action, $payload) {
            [$draft, $changes, $subject] = $this->drafts->act($draft, (int) $request->input('revision'), self::userId($request), $action, $payload);

            return response()->json([...$this->payload($draft), 'subject' => $subject, 'changed' => count($changes)]);
        });
    }

    public function import(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $request->validate(['revision' => 'required|integer|min:0', 'document' => 'required|array']);
        $document = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['document'];

        return $this->guard(function () use ($request, $draft, $document) {
            [$draft, $changes] = $this->drafts->import($draft, (int) $request->input('revision'), self::userId($request), $document);

            return response()->json([...$this->payload($draft), 'changed' => count($changes)]);
        });
    }

    public function export(ProgressionDraft $draft): JsonResponse
    {
        return response()->json($draft->document, Response::HTTP_OK, [
            'Content-Disposition' => "attachment; filename=\"progression-draft-{$draft->id}-r{$draft->revision}.json\"",
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function undo(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $request->validate(['revision' => 'required|integer|min:0']);

        return $this->guard(fn () => response()->json($this->payload($this->drafts->undo($draft, (int) $request->input('revision'), self::userId($request)))));
    }

    public function redo(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $request->validate(['revision' => 'required|integer|min:0']);

        return $this->guard(fn () => response()->json($this->payload($this->drafts->redo($draft, (int) $request->input('revision'), self::userId($request)))));
    }

    /** The audit log of the draft, newest first. */
    public function history(ProgressionDraft $draft): JsonResponse
    {
        return response()->json([
            'draft' => $draft->id,
            'entries' => $this->history->entries($draft)->map(fn (ProgressionDraftEntry $e) => [
                'id' => $e->id,
                'type' => $e->type,
                'action' => $e->action,
                'subject' => $e->subject,
                'state' => $e->state,
                'target_id' => $e->target_id,
                'revision' => $e->revision,
                'user' => $e->user?->name,
                'at' => $e->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function validateDraft(ProgressionDraft $draft): JsonResponse
    {
        $findings = $this->publisher->findings($draft);

        return response()->json([
            'valid' => !array_any($findings, fn (Finding $f) => $f->isError()),
            'warnings' => count(array_filter($findings, fn (Finding $f) => $f->severity === 'warning')),
            'findings' => $findings,
        ]);
    }

    /** `against`: `published` (default) or a snapshot version. */
    public function diff(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $validated = $request->validate(['against' => ['sometimes', 'regex:/^(published|[1-9][0-9]*)$/']]);
        $against = $validated['against'] ?? 'published';

        return $this->guard(fn () => response()->json($this->publisher->diff($draft, $against === 'published' ? 'published' : (int) $against)));
    }

    public function publish(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'confirm_warnings' => 'sometimes|boolean',
        ]);

        return $this->guard(function () use ($request, $draft, $validated) {
            try {
                [$version, $findings] = $this->publisher->publish($draft, self::userId($request), $validated['notes'] ?? null, (bool) ($validated['confirm_warnings'] ?? false));
            } catch (PublishRefused $refused) {
                return response()->json([
                    'message' => __("api.progression_editor.{$refused->reason}"),
                    'reason' => $refused->reason,
                    'needs_confirmation' => $refused->reason === 'publish_needs_confirmation',
                    'findings' => $refused->findings,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'message' => __('api.progression.published'),
                'version' => $version,
                'findings' => $findings,
                'draft' => $this->payload($draft->refresh()),
            ], Response::HTTP_CREATED);
        });
    }

    public function discard(Request $request, ProgressionDraft $draft): JsonResponse
    {
        return $this->guard(function () use ($request, $draft) {
            $this->drafts->discard($draft, self::userId($request));

            return response()->json($this->payload($draft->refresh()));
        });
    }

    private function payload(ProgressionDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'status' => $draft->status,
            'revision' => $draft->revision,
            'base_version' => $draft->base_version,
            'published_version' => $draft->published_version,
            'updated_at' => $draft->updated_at?->toIso8601String(),
            'can_undo' => $this->history->nextUndo($draft) !== null,
            'can_redo' => $this->history->nextRedo($draft) !== null,
            'document' => $draft->document,
        ];
    }

    private function guard(callable $handler): JsonResponse
    {
        try {
            return $handler();
        } catch (DraftConflict $conflict) {
            return response()->json(['message' => __('api.progression_editor.conflict'), 'revision' => $conflict->revision], Response::HTTP_CONFLICT);
        } catch (DraftActionRefused $refused) {
            return response()->json(['message' => __("api.progression_editor.{$refused->reason}", $refused->params), 'reason' => $refused->reason], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private static function userId(Request $request): ?int
    {
        return $request->user() instanceof User ? $request->user()->getKey() : null;
    }
}
