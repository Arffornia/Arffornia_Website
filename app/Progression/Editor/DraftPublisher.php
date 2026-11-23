<?php

namespace App\Progression\Editor;

use App\Models\ProgressionDraft;
use App\Progression\Finding;
use App\Progression\ModelDiff;
use App\Progression\ModelService;
use App\Progression\ModelValidator;
use App\Progression\RetiredValidations;
use App\Progression\SnapshotStore;
use Illuminate\Support\Facades\DB;

/**
 * Validate, diff and publish an editor draft (spec 019). Publishing writes the draft into the editable
 * tables and freezes them as the next snapshot in one transaction; errors refuse it, warnings need a
 * confirmation.
 */
class DraftPublisher
{
    public function __construct(
        private readonly ModelValidator $validator,
        private readonly ModelService $models,
        private readonly SnapshotStore $snapshots,
        private readonly ModelDiff $diff,
        private readonly DraftService $drafts,
        private readonly DraftHistory $history,
    ) {
    }

    /** @return list<Finding> errors first, then warnings, then notes */
    public function findings(ProgressionDraft $draft): array
    {
        $findings = $this->validator->validate(RetiredValidations::strip($draft->document));
        if ($draft->isOpen() && $this->drafts->tablesHash() !== $draft->base_hash) {
            $findings[] = new Finding('warning', 'tables_changed', 'tables', 'the live tables changed since this draft was opened (in-game commands or the graph tool); publishing replaces those changes');
        }
        $rank = ['error' => 0, 'warning' => 1, 'info' => 2];
        usort($findings, fn (Finding $a, Finding $b) => $rank[$a->severity] <=> $rank[$b->severity]);

        return $findings;
    }

    /**
     * @param  'published'|int  $against
     * @return array<string, mixed>
     */
    public function diff(ProgressionDraft $draft, string|int $against): array
    {
        $snapshot = $against === 'published' ? $this->snapshots->latest() : ($this->snapshots->find($against) ?? throw new DraftActionRefused('unknown_version', ['version' => $against]));
        $base = $snapshot === null ? ['stages' => [], 'graphs' => [], 'milestones' => []] : json_decode($snapshot->document, true, 512, JSON_THROW_ON_ERROR);
        $diff = $this->diff->between($base, $draft->document);

        return ['against' => $snapshot?->version, ...$diff, 'marks' => ModelDiff::marks($diff)];
    }

    /** @return array{0: ?int, 1: list<Finding>} the published version, or null and why not */
    public function publish(ProgressionDraft $draft, ?int $userId, ?string $notes, bool $confirmWarnings): array
    {
        return DB::transaction(function () use ($draft, $userId, $notes, $confirmWarnings) {
            $locked = ProgressionDraft::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isOpen()) {
                throw new DraftActionRefused('draft_closed');
            }
            $findings = $this->findings($locked);
            if (array_any($findings, fn (Finding $f) => $f->isError())) {
                throw new PublishRefused('publish_errors', $findings);
            }
            if (!$confirmWarnings && array_any($findings, fn (Finding $f) => $f->severity === 'warning')) {
                throw new PublishRefused('publish_needs_confirmation', $findings);
            }

            $document = $locked->document;
            $written = $this->models->replaceDraft($document);
            if (!$written['applied']) {
                throw new PublishRefused('publish_errors', $written['findings']);
            }
            $result = $this->models->publish($userId, $notes, acceptRuleErrors: false);
            if ($result['snapshot'] === null) {
                // The tables serialise to something the draft did not hold: undo the write, keep the draft.
                throw new PublishRefused('publish_errors', $result['findings']);
            }

            $locked->status = ProgressionDraft::STATUS_PUBLISHED;
            $locked->published_version = $result['snapshot']->version;
            $locked->save();
            $this->history->log($locked, $userId, 'publish', "version {$result['snapshot']->version}");

            return [$result['snapshot']->version, $findings];
        });
    }
}
