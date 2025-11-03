<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'img_url',
        'payment_type',
        'price',
        'promo_price',
        'currency',
        'coins_awarded',
        'is_unique',
        'show_in_newest',
        'allow_discounts',
        'commands'
    ];

    protected $casts = [
        'commands' => 'array',
        'is_unique' => 'boolean',
        'show_in_newest' => 'boolean',
        'allow_discounts' => 'boolean',
        'price' => 'integer',
        'promo_price' => 'integer',
    ];

    public function userSales()
    {
        return $this->hasMany(UserSale::class);
    }
}
