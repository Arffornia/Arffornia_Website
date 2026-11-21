<?php

namespace App\Rewards;

use App\Rewards\Types\ItemReward;
use App\Rewards\Types\LuckyBoxReward;
use App\Rewards\Types\PermissionReward;
use App\Rewards\Types\RankReward;
use App\Rewards\Types\RewardType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class RewardCatalog
{
    public const MAX_ENTRIES = 64;

    /** @var array<string, class-string<RewardType>> */
    public const TYPES = [
        'item' => ItemReward::class,
        'permission' => PermissionReward::class,
        'rank' => RankReward::class,
        'lucky_box' => LuckyBoxReward::class,
    ];

    /**
     * Validates a list of reward entries and returns them normalized.
     *
     * @param mixed $entries
     * @return list<array<string, mixed>>
     * @throws ValidationException
     */
    public static function validate(mixed $entries): array
    {
        Validator::make(['entries' => $entries], [
            'entries' => ['required', 'array', 'list', 'max:' . self::MAX_ENTRIES],
            'entries.*' => ['required', 'array'],
            'entries.*.type' => ['required', 'string', 'in:' . implode(',', array_keys(self::TYPES))],
        ])->validate();

        $normalized = [];
        foreach ($entries as $index => $entry) {
            $type = self::TYPES[$entry['type']];
            $validated = Validator::make($entry, $type::rules())->validate();
            $normalized[$index] = $type::normalize($validated);
        }

        return $normalized;
    }
}
