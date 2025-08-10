<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'ingredients',
        'result',
        'energy',
        'time',
        'milestone_unlock_id',
    ];

    protected $casts = [
        'ingredients' => 'array',
        'result' => 'array',
    ];

    public function milestoneUnlock()
    {
        return $this->belongsTo(MilestoneUnlock::class);
    }
}
