<?php

namespace App\Rewards;

use App\Rewards\Types\ItemReward;
use App\Rewards\Types\PermissionReward;
use App\Rewards\Types\RankReward;

/**
 * Turns the console command strings stored before typed rewards into reward entries.
 * Only known shapes aimed at the reward's own player are converted; nothing is ever executed.
 */
final class LegacyCommandConverter
{
    private const ITEM = '[a-z0-9_.-]+(?::[a-z0-9_.\/-]+)?';
    private const TARGET = '(\S+)';

    /**
     * @param list<mixed> $commands
     * @param string|null $playerName the reward owner's name, accepted as a target besides `{player}`
     * @return array{entries: list<array<string, mixed>>, dropped: list<string>, unsupported: list<string>}
     */
    public function convert(array $commands, ?string $playerName): array
    {
        $result = ['entries' => [], 'dropped' => [], 'unsupported' => []];

        foreach ($commands as $command) {
            if (!is_string($command) || preg_match('/[\r\n]/', $command)) {
                $result['unsupported'][] = is_string($command) ? $command : json_encode($command);
                continue;
            }

            $line = ltrim(trim($command), '/');
            $entry = $this->convertLine($line, $playerName);

            if ($entry !== null) {
                $result['entries'][] = $entry;
            } elseif (preg_match('/^(say|tellraw)\s/i', $line)) {
                $result['dropped'][] = $command;
            } else {
                $result['unsupported'][] = $command;
            }
        }

        return $result;
    }

    private function convertLine(string $line, ?string $playerName): ?array
    {
        if (preg_match('/^give\s+' . self::TARGET . '\s+(' . self::ITEM . ')(?:\s+(\d{1,6}))?$/', $line, $m)) {
            $item = str_contains($m[2], ':') ? $m[2] : 'minecraft:' . $m[2];
            $count = isset($m[3]) ? (int) $m[3] : 1;
            if (!$this->isOwnTarget($m[1], $playerName) || !preg_match(ItemReward::ID_PATTERN, $item)
                || $count < 1 || $count > ItemReward::MAX_COUNT) {
                return null;
            }

            return ['type' => 'item', 'item' => $item, 'count' => $count];
        }

        if (preg_match('/^lp\s+user\s+' . self::TARGET . '\s+permission\s+set\s+(\S+)(?:\s+(true|false))?$/i', $line, $m)) {
            $node = strtolower($m[2]);
            if (!$this->isOwnTarget($m[1], $playerName) || !preg_match(PermissionReward::NODE_PATTERN, $node)) {
                return null;
            }

            return ['type' => 'permission', 'node' => $node, 'value' => strtolower($m[3] ?? 'true') === 'true'];
        }

        if (preg_match('/^lp\s+user\s+' . self::TARGET . '\s+parent\s+add\s+(\S+)$/i', $line, $m)) {
            $group = strtolower($m[2]);
            if (!$this->isOwnTarget($m[1], $playerName) || !preg_match(RankReward::GROUP_PATTERN, $group)) {
                return null;
            }

            return ['type' => 'rank', 'group' => $group];
        }

        return null;
    }

    /**
     * The `commands` form of item entries, for game servers still on the JDBC mod. Remove with the `commands` column.
     *
     * @param list<array<string, mixed>> $entries
     * @return list<string>
     */
    public static function toLegacyCommands(array $entries): array
    {
        return array_values(array_map(
            fn (array $e) => "give {player} {$e['item']} {$e['count']}",
            array_filter($entries, fn (array $e) => $e['type'] === 'item')
        ));
    }

    private function isOwnTarget(string $target, ?string $playerName): bool
    {
        return $target === '{player}' || ($playerName !== null && strcasecmp($target, $playerName) === 0);
    }
}
