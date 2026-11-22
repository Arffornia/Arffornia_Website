<?php

namespace App\Progression\Editor;

use App\Models\ProgressionDraft;
use App\Models\ProgressionDraftEntry;
use Illuminate\Support\Collection;

/**
 * Undo, redo and the audit log of a draft. Edits form one linear stack: undo reverts the newest applied
 * edit, redo replays the oldest undone one, and a new edit drops whatever was undone.
 */
class DraftHistory
{
    public function log(ProgressionDraft $draft, ?int $userId, string $type, ?string $subject = null, ?int $targetId = null): ProgressionDraftEntry
    {
        return ProgressionDraftEntry::create([
            'draft_id' => $draft->id,
            'user_id' => $userId,
            'type' => $type,
            'subject' => $subject === null ? null : mb_substr($subject, 0, 191),
            'target_id' => $targetId,
            'revision' => $draft->revision,
        ]);
    }

    /** @param list<array<string, mixed>> $changes */
    public function recordEdit(ProgressionDraft $draft, ?int $userId, string $action, ?string $subject, array $changes): void
    {
        ProgressionDraftEntry::where('draft_id', $draft->id)->where('type', 'edit')->where('state', ProgressionDraftEntry::UNDONE)
            ->update(['state' => ProgressionDraftEntry::DROPPED]);
        ProgressionDraftEntry::create([
            'draft_id' => $draft->id,
            'user_id' => $userId,
            'type' => 'edit',
            'action' => $action,
            'subject' => $subject === null ? null : mb_substr($subject, 0, 191),
            'changes' => $changes,
            'state' => ProgressionDraftEntry::APPLIED,
            'revision' => $draft->revision,
        ]);
    }

    public function nextUndo(ProgressionDraft $draft): ?ProgressionDraftEntry
    {
        return ProgressionDraftEntry::where('draft_id', $draft->id)->where('type', 'edit')->where('state', ProgressionDraftEntry::APPLIED)->orderByDesc('id')->first();
    }

    public function nextRedo(ProgressionDraft $draft): ?ProgressionDraftEntry
    {
        return ProgressionDraftEntry::where('draft_id', $draft->id)->where('type', 'edit')->where('state', ProgressionDraftEntry::UNDONE)->orderBy('id')->first();
    }

    /** @return Collection<int, ProgressionDraftEntry> newest first */
    public function entries(ProgressionDraft $draft, int $limit = 200): Collection
    {
        return ProgressionDraftEntry::with('user:id,name')->where('draft_id', $draft->id)->orderByDesc('id')->limit($limit)->get();
    }
}
