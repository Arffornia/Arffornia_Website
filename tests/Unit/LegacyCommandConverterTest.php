<?php

namespace Tests\Unit;

use App\Rewards\LegacyCommandConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LegacyCommandConverterTest extends TestCase
{
    public static function convertible(): array
    {
        return [
            'give with placeholder' => ['give {player} minecraft:diamond 8', ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 8]],
            'give with leading slash and no count' => ['/give {player} minecraft:diamond', ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 1]],
            'give without namespace' => ['give {player} diamond 2', ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 2]],
            'give to the owner by name' => ['give steve mekanism:ingot_osmium 4', ['type' => 'item', 'item' => 'mekanism:ingot_osmium', 'count' => 4]],
            'lp permission' => ['lp user {player} permission set cosmetic.pet.kitten true', ['type' => 'permission', 'node' => 'cosmetic.pet.kitten', 'value' => true]],
            'lp permission without value' => ['lp user {player} permission set cosmetic.hat', ['type' => 'permission', 'node' => 'cosmetic.hat', 'value' => true]],
            'lp parent add' => ['lp user {player} parent add vip', ['type' => 'rank', 'group' => 'vip']],
        ];
    }

    #[DataProvider('convertible')]
    public function test_known_command_shapes_become_typed_entries(string $command, array $expected): void
    {
        $result = (new LegacyCommandConverter())->convert([$command], 'Steve');

        $this->assertSame([$expected], $result['entries']);
    }

    public static function unsupported(): array
    {
        return [
            'xp' => ['xp add {player} 500 levels'],
            'give to someone else' => ['give Alex minecraft:diamond 1'],
            'give to a selector' => ['give @a minecraft:diamond 1'],
            'give with components' => ['give {player} minecraft:diamond_sword[enchantments={levels:{sharpness:5}}] 1'],
            'give above the count cap' => ['give {player} minecraft:diamond 999999'],
            'lp parent set' => ['lp user {player} parent set admin'],
            'lp on another user' => ['lp user Alex permission set some.node true'],
            'op' => ['op {player}'],
            'execute' => ['execute as {player} run give {player} minecraft:diamond 1'],
            'multi-line' => ["give {player} minecraft:diamond 1\nop {player}"],
        ];
    }

    #[DataProvider('unsupported')]
    public function test_any_other_command_is_reported_unsupported(string $command): void
    {
        $result = (new LegacyCommandConverter())->convert([$command], 'Steve');

        $this->assertSame([], $result['entries']);
        $this->assertSame([$command], $result['unsupported']);
    }

    public function test_announcements_are_dropped_not_flagged(): void
    {
        $result = (new LegacyCommandConverter())->convert(['say {player} bought a kitten', 'lp user {player} permission set cosmetic.pet.kitten true'], null);

        $this->assertSame(['say {player} bought a kitten'], $result['dropped']);
        $this->assertSame([], $result['unsupported']);
        $this->assertCount(1, $result['entries']);
    }

    public function test_item_entries_are_written_back_as_give_commands_for_the_legacy_mod(): void
    {
        $commands = LegacyCommandConverter::toLegacyCommands([
            ['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 2],
            ['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 1],
        ]);

        $this->assertSame(['give {player} minecraft:diamond 2'], $commands);
    }
}
