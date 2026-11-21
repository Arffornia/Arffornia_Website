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

        $headers = ['Cache-Control' => 'public, max-age=60', 'X-Model-Version' => (string) $snapshot->version];
        if (in_array('"' . $snapshot->etag . '"', $request->getETags(), true)) {
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
        ['applied' => $applied, 'findings' => $findings] = $this->models->replaceDraft($request->input('document'));

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
}
