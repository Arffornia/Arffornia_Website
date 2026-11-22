<?php

namespace App\Rewards\Types;

/**
 * Crate keys (spec 020): `crate` is the crate id `ns:path`, the mod's `data/<ns>/arffornia/crate/<path>.json`.
 * The mod delivers them as `arffornia:crate_key` items bound to the player.
 */
final class KeyReward implements RewardType
{
    public const MAX_COUNT = 64;

    public static function rules(): array
    {
        return [
            'crate' => ['required', 'string', 'max:128', 'regex:' . ItemReward::ID_PATTERN],
            'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_COUNT],
        ];
    }

    public static function normalize(array $validated): array
    {
        return ['type' => 'key', 'crate' => $validated['crate'], 'count' => (int) $validated['count']];
    }

    /** Keys of one crate and owner stack like items, to 64. */
    public static function slots(array $entry): int
    {
        return intdiv($entry['count'] + ItemReward::STACK_SIZE - 1, ItemReward::STACK_SIZE);
    }
}
