<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShopItem;

class ShopItemSeeder extends Seeder
{
    public function run(): void
    {
        ShopItem::create([
            'name' => '20 000 Coins Pack',
            'description' => 'A pack of 20 000 coins for use in the in-game shop.',
            'img_url' => 'images/coins.png',
            'payment_type' => 'real_money',
            'price' => 10,
            'currency' => 'EUR',
            'coins_awarded' => 20000,
            'show_in_newest' => false,
            'allow_discounts' => false,
        ]);

        ShopItem::create([
            'name' => '42 000 Coins Pack',
            'description' => 'A pack of 42 000 coins for use in the in-game shop.',
            'img_url' => 'images/coins.png',
            'payment_type' => 'real_money',
            'price' => 20,
            'currency' => 'EUR',
            'coins_awarded' => 42000,
            'show_in_newest' => false,
            'allow_discounts' => false,
        ]);

        ShopItem::create([
            'name' => '110 000 Coins Pack',
            'description' => 'A pack of 110 000 coins for use in the in-game shop.',
            'img_url' => 'images/coins.png',
            'payment_type' => 'real_money',
            'price' => 50,
            'currency' => 'EUR',
            'coins_awarded' => 110000,
            'show_in_newest' => false,
            'allow_discounts' => false,
        ]);
    }
}
