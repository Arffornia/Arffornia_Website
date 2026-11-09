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
 * @property array<array-key, mixed>|null $commands
 * @property list<array<string, mixed>>|null $contents
 * @property string $status
 * @property string $source
 * @property string|null $claim_key
 * @property \Illuminate\Support\Carbon|null $claimed_at
 * @property string|null $hold_reason
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

    public const STATUS_PENDING = 'pending';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ON_HOLD = 'on_hold';

    public const SOURCE_SHOP = 'shop';
    public const SOURCE_VOTE = 'vote';
    public const SOURCE_STREAK = 'streak';

    /**
     * `commands` is the legacy console-command form, still written while game servers run a mod version that
     * reads it over JDBC. Delivery uses `contents`.
     */
    protected $fillable = [
        'user_id',
        'shop_item_id',
        'commands',
        'contents',
        'status',
        'source',
        'claim_key',
        'claimed_at',
        'hold_reason',
    ];

    protected $casts = [
        'commands' => 'array',
        'contents' => 'array',
        'claimed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
