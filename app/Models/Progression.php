<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Progression extends Model
{
    use HasFactory;
    protected $fillable = ['max_stage_id', 'current_milestone_id', 'completed_milestones'];
    protected $casts = ['completed_milestones' => 'array'];
}
