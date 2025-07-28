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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereShopItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereUserId($value)
 * @mixin \Eloquent
 */
class UserSale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'shop_item_id',
    ];

    protected $table = 'user_sale';
}
