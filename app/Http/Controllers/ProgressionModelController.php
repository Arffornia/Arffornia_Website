<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Progression\Finding;
use App\Progression\ModelService;
use App\Progression\SnapshotStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Spec 017: the published model, and the admin draft it is published from. */
class ProgressionModelController extends Controller
{
    public function __construct(
        private readonly SnapshotStore $snapshots,
        private readonly ModelService $models,
    ) {
    }

    /** The published snapshot, byte for byte as frozen; clients cache it by version and revalidate with the ETag. */
    public function show(Request $request): Response
    {
        $request->validate(['version' => 'sometimes|integer|min:1']);
        $snapshot = $request->has('version') ? $this->snapshots->find((int) $request->query('version')) : $this->snapshots->latest();
        if ($snapshot === null) {
            return response()->json(['message' => __('api.progression.no_model')], Response::HTTP_NOT_FOUND);
        }

        // A numbered version never changes; the bare URL moves on each publish. The document's own
        // `status` is the one it was frozen with; X-Model-Status says whether it is still the current one.
        $headers = [
            'Cache-Control' => $request->has('version') ? 'public, max-age=31536000, immutable' : 'public, max-age=60',
            'X-Model-Version' => (string) $snapshot->version,
            'X-Model-Status' => $snapshot->status,
        ];
        if (self::etagMatches($request->getETags(), $snapshot->etag)) {
            return response('', Response::HTTP_NOT_MODIFIED, $headers)->setEtag($snapshot->etag);
        }

        return response($snapshot->document, Response::HTTP_OK, [...$headers, 'Content-Type' => 'application/json'])->setEtag($snapshot->etag);
    }

    public function draft(): JsonResponse
    {
        ['document' => $document, 'findings' => $findings] = $this->models->draft();

        return response()->json(['document' => $document, 'findings' => $findings]);
    }

    /** Replaces the draft with a whole document, matched by key (graph tool, AI skill, import). */
    public function replaceDraft(Request $request): JsonResponse
    {
        $request->validate(['document' => 'required|array']);
        // The raw body, not the input bag: the global middleware would trim strings and turn "" into null.
        $document = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['document'];
        ['applied' => $applied, 'findings' => $findings] = $this->models->replaceDraft($document);

        if (!$applied) {
            return response()->json(['message' => __('api.progression.draft_invalid'), 'findings' => $findings], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => __('api.progression.draft_saved'), 'findings' => $findings]);
    }

    public function validateDraft(): JsonResponse
    {
        $findings = $this->models->draft()['findings'];

        return response()->json([
            'valid' => !array_any($findings, fn (Finding $f) => $f->isError()),
            'findings' => $findings,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'accept_rule_errors' => 'sometimes|boolean',
        ]);

        $result = $this->models->publish($request->user() instanceof User ? $request->user()->getKey() : null, $validated['notes'] ?? null, (bool) ($validated['accept_rule_errors'] ?? false));
        if ($result['snapshot'] === null) {
            return response()->json(['message' => __('api.progression.publish_blocked'), 'findings' => $result['findings']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => __('api.progression.published'),
            'version' => $result['snapshot']->version,
            'etag' => $result['snapshot']->etag,
            'findings' => $result['findings'],
        ], Response::HTTP_CREATED);
    }

    /** If-None-Match: `*`, strong or weak (`W/"..."`) tags all match by value. */
    private static function etagMatches(array $tags, string $etag): bool
    {
        foreach ($tags as $tag) {
            if ($tag === '*' || trim(preg_replace('/^W\//', '', $tag), '"') === $etag) {
                return true;
            }
        }

        return false;
    }
}
