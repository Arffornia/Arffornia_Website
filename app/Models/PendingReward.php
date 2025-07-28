<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $shop_item_id
 * @property array<array-key, mixed> $commands
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereCommands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereShopItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereUserId($value)
 * @mixin \Eloquent
 */
class PendingReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_item_id',
        'commands',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'commands' => 'array',
    ];
}
