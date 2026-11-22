<?php

namespace Tests\Unit\Progression;

use App\Progression\Editor\DraftChanges;
use PHPUnit\Framework\TestCase;

class DraftChangesTest extends TestCase
{
    private static function document(array $milestones, array $stages = [1]): array
    {
        return [
            'stages' => array_map(fn ($n) => ['number' => $n, 'name' => ['en' => "Stage {$n}"], 'threshold' => 0.8, 'module' => []], $stages),
            'graphs' => [['key' => 'g', 'name' => ['en' => 'G'], 'branch' => 'neutral']],
            'milestones' => array_map(fn ($key) => ['key' => $key, 'graph' => 'g', 'stage' => 1, 'title' => ['en' => $key], 'requires' => []], $milestones),
        ];
    }

    public function test_applying_the_changes_forward_gives_the_new_document_and_in_reverse_the_old_one(): void
    {
        $before = self::document(['g/a', 'g/b', 'g/c']);
        $after = self::document(['g/a', 'g/c', 'g/d'], [1, 2]);
        $after['milestones'][0]['title'] = ['en' => 'Renamed'];

        $changes = DraftChanges::between($before, $after);

        $this->assertSame($after, DraftChanges::apply($before, $changes));
        $this->assertSame($before, DraftChanges::apply($after, $changes, reverse: true));
    }

    public function test_identical_documents_have_no_changes(): void
    {
        $this->assertSame([], DraftChanges::between(self::document(['g/a']), self::document(['g/a'])));
    }

    public function test_a_removed_entity_comes_back_at_its_old_position(): void
    {
        $before = self::document(['g/a', 'g/b', 'g/c']);
        $after = self::document(['g/a', 'g/c']);

        $restored = DraftChanges::apply($after, DraftChanges::between($before, $after), reverse: true);

        $this->assertSame(['g/a', 'g/b', 'g/c'], array_column($restored['milestones'], 'key'));
    }
}
