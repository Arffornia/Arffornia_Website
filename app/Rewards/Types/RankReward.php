<?php

namespace App\Rewards\Types;

/** A LuckPerms group added to the player's parents. */
final class RankReward implements RewardType
{
    public const GROUP_PATTERN = '/^[a-z0-9_-]{1,36}$/';

    public static function rules(): array
    {
        return [
            'group' => ['required', 'string', 'regex:' . self::GROUP_PATTERN],
        ];
    }

    public static function normalize(array $validated): array
    {
        return ['type' => 'rank', 'group' => $validated['group']];
    }
}
