<?php

namespace Tests\Unit\Progression;

use App\Progression\ModelDiff;
use App\Progression\ModelValidator;
use Tests\TestCase;

class ModelDiffTest extends TestCase
{
    private static function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/fixtures/progression/{$name}.json")), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_the_fixtures_follow_the_schema(): void
    {
        $this->assertSame([], app(ModelValidator::class)->schemaErrors(self::fixture('diff-v1')));
        $this->assertSame([], app(ModelValidator::class)->schemaErrors(self::fixture('diff-v2')));
    }

    public function test_v1_to_v2_gives_the_known_changes(): void
    {
        $diff = (new ModelDiff())->between(self::fixture('diff-v1'), self::fixture('diff-v2'));

        $this->assertSame(['added' => [3], 'removed' => [], 'changed' => [['key' => 2, 'fields' => ['threshold']]]], $diff['stages']);
        $this->assertSame(['added' => [], 'removed' => [], 'changed' => [['key' => 'mek', 'fields' => ['name']]]], $diff['graphs']);
        $this->assertSame([
            'added' => ['arffornia/e'],
            'removed' => ['mek/d'],
            'moved' => [['key' => 'arffornia/c', 'from' => ['stage' => 2, 'graph' => 'arffornia'], 'to' => ['stage' => 3, 'graph' => 'arffornia']]],
            'changed' => [['key' => 'arffornia/a', 'fields' => ['title']]],
        ], $diff['milestones']);
        $this->assertSame([
            'added' => [['from' => 'arffornia/a', 'to' => 'arffornia/c'], ['from' => 'arffornia/a', 'to' => 'arffornia/e']],
            'removed' => [['from' => 'arffornia/b', 'to' => 'arffornia/c']],
        ], $diff['links']);
        $this->assertSame([
            'added' => [['milestone' => 'arffornia/b', 'item' => 'minecraft:blast_furnace']],
            'removed' => [],
            'changed' => [['milestone' => 'arffornia/b', 'item' => 'minecraft:furnace', 'fields' => ['gate_mode']]],
        ], $diff['unlocks']);
        $this->assertFalse($diff['empty']);
        $this->assertEquals(['arffornia/a' => 'changed', 'arffornia/c' => 'moved', 'arffornia/b' => 'changed', 'arffornia/e' => 'added'], ModelDiff::marks($diff));
    }

    public function test_a_document_against_itself_has_no_changes(): void
    {
        $this->assertTrue((new ModelDiff())->between(self::fixture('diff-v1'), self::fixture('diff-v1'))['empty']);
    }
}
