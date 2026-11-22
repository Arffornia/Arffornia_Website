<?php

namespace App\Http\Controllers;

use App\Models\ProgressionDraft;
use App\Models\User;
use App\Progression\Editor\DraftActionRefused;
use App\Progression\Editor\DraftConflict;
use App\Progression\Editor\DraftEditor;
use App\Progression\Editor\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/** Spec 019: the admin editor's draft. Admin only (routes/api.php). */
class ProgressionDraftController extends Controller
{
    public function __construct(private readonly DraftService $drafts)
    {
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
            [$draft, $changes, $subject] = $this->drafts->act($draft, (int) $request->input('revision'), $action, $payload);

            return response()->json([...$this->payload($draft), 'subject' => $subject, 'changed' => count($changes)]);
        });
    }

    public function import(Request $request, ProgressionDraft $draft): JsonResponse
    {
        $request->validate(['revision' => 'required|integer|min:0', 'document' => 'required|array']);
        $document = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['document'];

        return $this->guard(function () use ($request, $draft, $document) {
            [$draft, $changes] = $this->drafts->import($draft, (int) $request->input('revision'), $document);

            return response()->json([...$this->payload($draft), 'changed' => count($changes)]);
        });
    }

    public function export(ProgressionDraft $draft): JsonResponse
    {
        return response()->json($draft->document, Response::HTTP_OK, [
            'Content-Disposition' => "attachment; filename=\"progression-draft-{$draft->id}-r{$draft->revision}.json\"",
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function discard(ProgressionDraft $draft): JsonResponse
    {
        return $this->guard(function () use ($draft) {
            $this->drafts->discard($draft);

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
