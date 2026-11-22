<?php

namespace App\Rewards;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/** Player-facing text for a reward entry, for the website's own pages. */
final class RewardLabel
{
    /** @param array<string, mixed> $entry A normalized entry from {@see RewardCatalog::validate()}. */
    public static function of(array $entry): string
    {
        return match ($entry['type']) {
            'key' => __('profile.streak.contents.key', ['count' => $entry['count'], 'crate' => self::crateName($entry['crate'])]),
            'lucky_box' => __('profile.streak.contents.lucky_box', ['count' => $entry['count']]),
            'item' => __('profile.streak.contents.item', ['count' => $entry['count'], 'item' => self::itemName($entry['item'])]),
            default => __('profile.streak.contents.perk'),
        };
    }

    /** The crate's name from lang/<locale>/crates.php, keyed like the mod's `crate.<ns>.<path>`. */
    public static function crateName(string $crateId): string
    {
        $key = 'crates.crate.' . str_replace([':', '/'], '.', $crateId);

        return Lang::has($key) ? __($key) : $crateId;
    }

    public static function itemName(string $itemId): string
    {
        return Str::headline(basename(Str::after($itemId, ':')));
    }
}
