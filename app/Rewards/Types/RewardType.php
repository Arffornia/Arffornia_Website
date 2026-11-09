<?php

namespace App\Rewards\Types;

/**
 * One kind of reward the game server knows how to deliver. A new kind (pets, cosmetics) is a new class
 * registered in {@see \App\Rewards\RewardCatalog::TYPES}; the mod skips types it does not know yet.
 */
interface RewardType
{
    /** Laravel validation rules for the entry fields other than `type`. */
    public static function rules(): array;

    /** The entry as stored and sent to the mod, from validated input. */
    public static function normalize(array $validated): array;
}
