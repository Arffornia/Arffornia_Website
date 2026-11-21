<?php

namespace App\Progression;

/** Read-side view of one published snapshot, indexed for state and stats. */
final class Snapshot
{
    /** @var array<string, array<string, mixed>> */
    public readonly array $milestones;

    /** @var array<int, array<string, mixed>> */
    public readonly array $stages;

    /** @var array<int, list<string>> */
    public readonly array $milestonesByStage;

    public function __construct(public readonly int $version, array $document)
    {
        $milestones = [];
        $byStage = [];
        foreach ($document['milestones'] as $milestone) {
            $milestones[$milestone['key']] = $milestone;
            $byStage[$milestone['stage']][] = $milestone['key'];
        }
        $stages = [];
        foreach ($document['stages'] as $stage) {
            $stages[$stage['number']] = $stage;
        }
        ksort($stages);

        $this->milestones = $milestones;
        $this->stages = $stages;
        $this->milestonesByStage = $byStage;
    }

    public function firstStage(): int
    {
        return (int) array_key_first($this->stages);
    }

    public function nextStage(int $number): ?int
    {
        foreach (array_keys($this->stages) as $candidate) {
            if ($candidate > $number) {
                return $candidate;
            }
        }

        return null;
    }
}
