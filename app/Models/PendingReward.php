<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
