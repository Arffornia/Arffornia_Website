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
            ['type' => 'key', 'crate' => 'arffornia:kit/starter', 'count' => '1', 'owner' => 'someone'],
        ]);

        $this->assertSame([
            ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 3],
            ['type' => 'permission', 'node' => 'cosmetic.pet.kitten', 'value' => true],
            ['type' => 'rank', 'group' => 'vip'],
            ['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2],
            ['type' => 'key', 'crate' => 'arffornia:kit/starter', 'count' => 1],
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
            'key without crate' => [[['type' => 'key', 'count' => 1]]],
            'key with a bad crate id' => [[['type' => 'key', 'crate' => 'Streak', 'count' => 1]]],
            'key with zero count' => [[['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 0]]],
            'items over 36 stacks' => [[['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 36 * 64 + 1]]],
            'items and a key over 36 slots' => [[
                ['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 36 * 64],
                ['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 1],
            ]],
            '37 lucky box rolls' => [[['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 37]]],
        ];
    }

    #[DataProvider('invalid')]
    public function test_invalid_entries_are_rejected(array $entries): void
    {
        $this->expectException(ValidationException::class);

        RewardCatalog::validate($entries);
    }

    public function test_a_reward_filling_exactly_36_slots_is_accepted(): void
    {
        $entries = [
            ['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 33 * 64],
            ['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 64],
            ['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2],
            ['type' => 'permission', 'node' => 'cosmetic.pet.kitten'],
            ['type' => 'rank', 'group' => 'vip'],
        ];

        $this->assertSame(36, RewardCatalog::slots(RewardCatalog::validate($entries)));
    }

    public function test_each_entry_counts_its_own_stacks(): void
    {
        $entries = RewardCatalog::validate([
            ['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 1],
            ['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 65],
        ]);

        $this->assertSame(3, RewardCatalog::slots($entries));
    }

    public function test_the_slot_error_says_how_many_slots_the_reward_needs(): void
    {
        try {
            RewardCatalog::validate([['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 40]]);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['This reward needs 40 inventory slots; the limit is 36.'], $e->errors()['entries']);
        }
    }
}
