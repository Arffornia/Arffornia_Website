<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneRequirement extends Model
{
    use HasFactory;
    protected $fillable = ['milestone_id', 'item_id', 'amount'];
}
