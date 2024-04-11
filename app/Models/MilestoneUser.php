<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneUser extends Model
{
    use HasFactory;

    protected $table = 'milestone_user';

    public function milestone() {
        return $this->belongsTo(Milestone::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
