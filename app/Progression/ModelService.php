<?php

namespace App\Progression;

use App\Models\Milestone;
use App\Models\ModelSnapshot;
use App\Models\ProgressionGraph;

/** The working draft (the editable tables) and its publication as a numbered snapshot. */
class ModelService
{
    public function __construct(
        private readonly ModelSerializer $serializer,
        private readonly ModelValidator $validator,
        private readonly DraftWriter $writer,
        private readonly SnapshotStore $snapshots,
        private readonly ProgressionStateService $states,
    ) {
    }

    /** Rows created before keys existed get one, in id order, as docs/scripts/progression.py `convert` does. */
    public function ensureKeys(): int
    {
        $assigned = 0;
        foreach (ProgressionGraph::whereNull('key')->orderBy('id')->get() as $graph) {
            $graph->key = Keys::forNewGraph($graph->name);
            $graph->save();
            $assigned++;
        }
        foreach (Milestone::whereNull('key')->orderBy('id')->get() as $milestone) {
            $milestone->key = Keys::forNewMilestone($milestone);
            $milestone->save();
            $assigned++;
        }

        return $assigned;
    }

    /** @return array{document: array, findings: list<Finding>} */
    public function draft(): array
    {
        $this->ensureKeys();
        [$document, $findings] = $this->serializer->draft();

        return ['document' => $document, 'findings' => self::bySeverity([...$findings, ...$this->validator->validate($document)])];
    }

    /** @return array{applied: bool, findings: list<Finding>} */
    public function replaceDraft(array $document): array
    {
        $schemaErrors = $this->validator->schemaErrors($document);
        if ($schemaErrors !== []) {
            return ['applied' => false, 'findings' => $schemaErrors];
        }
        $writeFindings = $this->writer->write($document);

        return ['applied' => true, 'findings' => self::bySeverity([...$writeFindings, ...$this->validator->ruleFindings($document)])];
    }

    /**
     * Schema errors always block. Rule errors (a backward link, a cycle) block unless the caller accepts
     * them, which the first snapshot of the hand-made tree needs.
     *
     * @return array{snapshot: ?ModelSnapshot, findings: list<Finding>}
     */
    public function publish(?int $userId, ?string $notes, bool $acceptRuleErrors): array
    {
        ['document' => $document, 'findings' => $findings] = $this->draft();
        $blocking = array_filter($findings, fn (Finding $f) => $f->isError() && ($f->code === 'schema' || !$acceptRuleErrors));
        if ($blocking !== []) {
            return ['snapshot' => null, 'findings' => $findings];
        }

        $snapshot = $this->snapshots->publish($document, $userId, $notes);
        $this->states->reevaluateAll();

        return ['snapshot' => $snapshot, 'findings' => $findings];
    }

    /**
     * @param  list<Finding>  $findings
     * @return list<Finding> errors first, then warnings, then notes
     */
    private static function bySeverity(array $findings): array
    {
        $rank = ['error' => 0, 'warning' => 1, 'info' => 2];
        usort($findings, fn (Finding $a, Finding $b) => $rank[$a->severity] <=> $rank[$b->severity]);

        return $findings;
    }
}
