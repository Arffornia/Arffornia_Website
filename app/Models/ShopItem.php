<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $img_url
 * @property int $category_id
 * @property int $real_price
 * @property int $promo_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSale> $userSales
 * @property-read int|null $user_sales_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereImgUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem wherePromoPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereRealPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ShopItem extends Model
{
    use HasFactory;

    protected $table = 'shop_items';

    protected $fillable = [
        'name',
        'description',
        'img_url',
        'category_id',
        'real_price',
        'promo_price'
    ];

    public function userSales() {
        return $this->hasMany(UserSale::class);
    }
}
