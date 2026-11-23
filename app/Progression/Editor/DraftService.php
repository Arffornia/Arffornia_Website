<?php

namespace App\Progression\Editor;

use App\Models\ProgressionDraft;
use App\Models\ProgressionDraftEntry;
use App\Progression\ModelSerializer;
use App\Progression\ModelValidator;
use App\Progression\RetiredValidations;
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
        private readonly DraftHistory $history,
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
                $document = RetiredValidations::strip(json_decode($snapshot->document, true, 512, JSON_THROW_ON_ERROR));
            } else {
                $document = $this->serializer->draft()[0];
            }
            $document['status'] = 'draft';

            $draft = ProgressionDraft::create([
                'status' => ProgressionDraft::STATUS_OPEN,
                'revision' => 0,
                'document' => $document,
                'base_version' => $fromVersion ?? $this->snapshots->latest()?->version,
                'base_hash' => $this->tablesHash(),
                'created_by' => $userId,
            ]);
            $this->history->log($draft, $userId, 'open', $fromVersion !== null ? "version {$fromVersion}" : 'tables');

            return [$draft, true];
        });
    }

    /** Hash of the editable tables as a document, to tell at publish whether something else wrote them. */
    public function tablesHash(): string
    {
        return hash('sha256', json_encode($this->serializer->draft()[0], JSON_THROW_ON_ERROR));
    }

    /** Runs $work on the draft row locked, open, and at the revision the editor saw. */
    private function locked(ProgressionDraft $draft, int $revision, callable $work): mixed
    {
        return DB::transaction(function () use ($draft, $revision, $work) {
            $locked = ProgressionDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isOpen()) {
                throw new DraftActionRefused('draft_closed');
            }
            if ($locked->revision !== $revision) {
                throw new DraftConflict($locked->revision);
            }

            return $work($locked);
        });
    }

    /**
     * @param  callable(array): array{0: array, 1: ?string}  $change
     * @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string}
     */
    private function change(ProgressionDraft $draft, int $revision, ?int $userId, string $action, callable $change): array
    {
        return $this->locked($draft, $revision, function (ProgressionDraft $locked) use ($userId, $action, $change) {
            $before = $locked->document;
            [$after, $subject] = $change($before);
            $changes = DraftChanges::between($before, $after);
            if ($changes !== []) {
                $locked->document = $after;
                $locked->revision++;
                $locked->save();
                $this->history->recordEdit($locked, $userId, $action, $subject, $changes);
            }

            return [$locked, $changes, $subject];
        });
    }

    /** @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string} */
    public function act(ProgressionDraft $draft, int $revision, ?int $userId, string $action, array $payload): array
    {
        return $this->change($draft, $revision, $userId, $action, fn (array $document) => $this->editor->apply($document, $action, $payload));
    }

    /** Reverts the newest applied edit. */
    public function undo(ProgressionDraft $draft, int $revision, ?int $userId): ProgressionDraft
    {
        return $this->locked($draft, $revision, function (ProgressionDraft $locked) use ($userId) {
            $entry = $this->history->nextUndo($locked) ?? throw new DraftActionRefused('nothing_to_undo');
            $locked->document = DraftChanges::apply($locked->document, $entry->changes, reverse: true);
            $locked->revision++;
            $locked->save();
            $entry->update(['state' => ProgressionDraftEntry::UNDONE]);
            $this->history->log($locked, $userId, 'undo', $entry->subject, $entry->id);

            return $locked;
        });
    }

    /** Replays the oldest undone edit. */
    public function redo(ProgressionDraft $draft, int $revision, ?int $userId): ProgressionDraft
    {
        return $this->locked($draft, $revision, function (ProgressionDraft $locked) use ($userId) {
            $entry = $this->history->nextRedo($locked) ?? throw new DraftActionRefused('nothing_to_redo');
            $locked->document = DraftChanges::apply($locked->document, $entry->changes);
            $locked->revision++;
            $locked->save();
            $entry->update(['state' => ProgressionDraftEntry::APPLIED]);
            $this->history->log($locked, $userId, 'redo', $entry->subject, $entry->id);

            return $locked;
        });
    }

    /**
     * Replaces the whole draft with an imported document (the JSON an export gave, or a snapshot).
     *
     * @return array{0: ProgressionDraft, 1: list<array<string, mixed>>, 2: ?string}
     */
    public function import(ProgressionDraft $draft, int $revision, ?int $userId, array $document): array
    {
        $document = RetiredValidations::strip($document);
        $errors = $this->validator->schemaErrors($document);
        if ($errors !== []) {
            throw new DraftActionRefused('import_invalid', ['detail' => $errors[0]->subject . ': ' . $errors[0]->message]);
        }
        $document['status'] = 'draft';

        return $this->change($draft, $revision, $userId, 'draft.import', fn () => [$document, null]);
    }

    public function discard(ProgressionDraft $draft, ?int $userId): void
    {
        if (!$draft->isOpen()) {
            throw new DraftActionRefused('draft_closed');
        }
        $draft->status = ProgressionDraft::STATUS_DISCARDED;
        $draft->save();
        $this->history->log($draft, $userId, 'discard');
    }
}
