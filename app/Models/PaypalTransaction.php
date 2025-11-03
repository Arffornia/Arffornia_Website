<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaypalTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paypal_order_id',
        'status',
        'amount',
        'currency',
        'coins_purchased',
        'paypal_response',
    ];

    protected $casts = [
        'paypal_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
