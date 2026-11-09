<?php

namespace App\Rewards\Types;

/** A LuckPerms permission node set on the player. */
final class PermissionReward implements RewardType
{
    public const NODE_PATTERN = '/^[a-z0-9_-]+(\.[a-z0-9_-]+)*$/';

    public static function rules(): array
    {
        return [
            'node' => ['required', 'string', 'max:128', 'regex:' . self::NODE_PATTERN],
            'value' => ['sometimes', 'boolean'],
        ];
    }

    public static function normalize(array $validated): array
    {
        return ['type' => 'permission', 'node' => $validated['node'], 'value' => (bool) ($validated['value'] ?? true)];
    }
}
