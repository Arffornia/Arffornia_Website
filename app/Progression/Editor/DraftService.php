<?php

namespace App\Progression\Editor;

use App\Models\ProgressionDraft;
use App\Progression\ModelSerializer;
use App\Progression\ModelValidator;
use App\Progression\SnapshotStore;
use Illuminate\Support\Facades\DB;

/** Opens, edits and imports the editor's draft (spec 019). One open draft at a time. */
class DraftService
{
    public function __construct(
        private readonly DraftEditor $editor,
        private readonly ModelSerializer $serializer,
        private readonly ModelValidator $validator,
        private readonly SnapshotStore $snapshots,
    ) {
    }

    public function open(): ?ProgressionDraft
    {
        return ProgressionDraft::where('status', ProgressionDraft::STATUS_OPEN)->latest('id')->first();
    }

    /**
     * The open draft, or a new one from the editable tables (what the mod reads today, so in-game edits
     * are kept) or from a published version.
     *
     * @return array{0: ProgressionDraft, 1: bool} the draft and whether it was created
     */
    public function openOrCreate(?int $userId, ?int $fromVersion): array
    {
        return DB::transaction(function () use ($userId, $fromVersion) {
            $open = $this->open();
            if ($open !== null) {
                return [$open, false];
            }
            if ($fromVersion !== null) {
                $snapshot = $this->snapshots->find($fromVersion) ?? throw new DraftActionRefused('unknown_version', ['version' => $fromVersion]);
                $document = json_decode($snapshot->document, true, 512, JSON_THROW_ON_ERROR);
            } else {
                $document = $this->serializer->draft()[0];
            }
            $document['status'] = 'draft';

            return [ProgressionDraft::create([
                'status' => ProgressionDraft::STATUS_OPEN,
                'revision' => 0,
                'document' => $document,
                'base_version' => $fromVersion ?? $this->snapshots->latest()?->version,
                'base_hash' => $this->tablesHash(),
                'created_by' => $userId,
            ]), true];
        });
    }

    /** Hash of the editable tables as a document, to tell at publish whether something else wrote them. */
    public function tablesHash(): string
    {
        return hash('sha256', json_encode($this->serializer->draft()[0], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  callable(array): array{0: array, 1: ?string}  $change
     * @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string}
     */
    private function change(ProgressionDraft $draft, int $revision, callable $change): array
    {
        return DB::transaction(function () use ($draft, $revision, $change) {
            $locked = ProgressionDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isOpen()) {
                throw new DraftActionRefused('draft_closed');
            }
            if ($locked->revision !== $revision) {
                throw new DraftConflict($locked->revision);
            }
            $before = $locked->document;
            [$after, $subject] = $change($before);
            $changes = DraftChanges::between($before, $after);
            if ($changes !== []) {
                $locked->document = $after;
                $locked->revision++;
                $locked->save();
            }

            return [$locked, $changes, $subject];
        });
    }

    /** @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string} */
    public function act(ProgressionDraft $draft, int $revision, string $action, array $payload): array
    {
        return $this->change($draft, $revision, fn (array $document) => $this->editor->apply($document, $action, $payload));
    }

    /**
     * Replaces the whole draft with an imported document (the JSON an export gave, or a snapshot).
     *
     * @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string}
     */
    public function import(ProgressionDraft $draft, int $revision, array $document): array
    {
        $errors = $this->validator->schemaErrors($document);
        if ($errors !== []) {
            throw new DraftActionRefused('import_invalid', ['detail' => $errors[0]->subject . ': ' . $errors[0]->message]);
        }
        $document['status'] = 'draft';

        return $this->change($draft, $revision, fn () => [$document, null]);
    }

    public function discard(ProgressionDraft $draft): void
    {
        if (!$draft->isOpen()) {
            throw new DraftActionRefused('draft_closed');
        }
        $draft->status = ProgressionDraft::STATUS_DISCARDED;
        $draft->save();
    }
}
