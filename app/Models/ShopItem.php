<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
