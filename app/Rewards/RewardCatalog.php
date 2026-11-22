<?php

namespace App\Rewards;

use App\Rewards\Types\ItemReward;
use App\Rewards\Types\KeyReward;
use App\Rewards\Types\LuckyBoxReward;
use App\Rewards\Types\PermissionReward;
use App\Rewards\Types\RankReward;
use App\Rewards\Types\RewardType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class RewardCatalog
{
    public const MAX_ENTRIES = 64;

    /** The player inventory without armour and offhand. The mod never drops a reward, so one must fit in it whole. */
    public const MAX_SLOTS = 36;

    /** @var array<string, class-string<RewardType>> */
    public const TYPES = [
        'item' => ItemReward::class,
        'permission' => PermissionReward::class,
        'rank' => RankReward::class,
        'lucky_box' => LuckyBoxReward::class,
        'key' => KeyReward::class,
    ];

    /**
     * Validates a list of reward entries and returns them normalized. A reward that needs more than MAX_SLOTS
     * inventory slots is refused: items and keys count one slot per stack of 64, a lucky box one slot per roll,
     * permissions and ranks none.
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

        $slots = self::slots($normalized);
        if ($slots > self::MAX_SLOTS) {
            throw ValidationException::withMessages([
                'entries' => __('api.reward.too_many_slots', ['slots' => $slots, 'max' => self::MAX_SLOTS]),
            ]);
        }

        return $normalized;
    }

    /** @param list<array<string, mixed>> $normalized */
    public static function slots(array $normalized): int
    {
        return array_sum(array_map(fn (array $entry) => self::TYPES[$entry['type']]::slots($entry), $normalized));
    }
}
