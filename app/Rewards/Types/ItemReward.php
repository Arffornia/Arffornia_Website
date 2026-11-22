<?php

namespace App\Rewards\Types;

final class ItemReward implements RewardType
{
    public const ID_PATTERN = '/^[a-z0-9_.-]+:[a-z0-9_.\/-]+$/';
    public const MAX_COUNT = 2304;
    // Some items stack to 16 or 1; the mod checks the real room, this is the website's upper bound for common items.
    public const STACK_SIZE = 64;

    public static function rules(): array
    {
        return [
            'item' => ['required', 'string', 'max:128', 'regex:' . self::ID_PATTERN],
            'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_COUNT],
        ];
    }

    public static function normalize(array $validated): array
    {
        return ['type' => 'item', 'item' => $validated['item'], 'count' => (int) $validated['count']];
    }

    public static function slots(array $entry): int
    {
        return intdiv($entry['count'] + self::STACK_SIZE - 1, self::STACK_SIZE);
    }
}
