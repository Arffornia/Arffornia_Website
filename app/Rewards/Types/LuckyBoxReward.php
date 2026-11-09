<?php

namespace App\Rewards\Types;

/** Rolls of a loot table the game server owns: box `ns:path` is table `ns:lucky_box/path`. The website never picks the loot. */
final class LuckyBoxReward implements RewardType
{
    public const MAX_COUNT = 64;

    public static function rules(): array
    {
        return [
            'box' => ['required', 'string', 'max:128', 'regex:' . ItemReward::ID_PATTERN],
            'count' => ['required', 'integer', 'min:1', 'max:' . self::MAX_COUNT],
        ];
    }

    public static function normalize(array $validated): array
    {
        return ['type' => 'lucky_box', 'box' => $validated['box'], 'count' => (int) $validated['count']];
    }
}
