<?php

namespace Tests\Feature;

use App\Rewards\RewardCatalog;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RewardCatalogTest extends TestCase
{
    public function test_valid_entries_are_normalized_and_extra_fields_dropped(): void
    {
        $entries = RewardCatalog::validate([
            ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => '3', 'nbt' => '{evil}'],
            ['type' => 'permission', 'node' => 'cosmetic.pet.kitten'],
            ['type' => 'rank', 'group' => 'vip'],
            ['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2],
        ]);

        $this->assertSame([
            ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 3],
            ['type' => 'permission', 'node' => 'cosmetic.pet.kitten', 'value' => true],
            ['type' => 'rank', 'group' => 'vip'],
            ['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2],
        ], $entries);
    }

    public static function invalid(): array
    {
        return [
            'empty list' => [[]],
            'unknown type' => [[['type' => 'command', 'command' => 'op x']]],
            'item without namespace' => [[['type' => 'item', 'item' => 'diamond', 'count' => 1]]],
            'item with components' => [[['type' => 'item', 'item' => 'minecraft:diamond[x]', 'count' => 1]]],
            'zero count' => [[['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 0]]],
            'huge count' => [[['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 100000]]],
            'permission wildcard' => [[['type' => 'permission', 'node' => '*']]],
            'permission with spaces' => [[['type' => 'permission', 'node' => 'a b']]],
            'rank with uppercase' => [[['type' => 'rank', 'group' => 'Admin; op']]],
            'not a list' => [['a' => ['type' => 'rank', 'group' => 'vip']]],
        ];
    }

    #[DataProvider('invalid')]
    public function test_invalid_entries_are_rejected(array $entries): void
    {
        $this->expectException(ValidationException::class);

        RewardCatalog::validate($entries);
    }
}
