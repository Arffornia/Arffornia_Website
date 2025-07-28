<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property int $max_stage_id
 * @property int|null $current_milestone_id
 * @property array<array-key, mixed>|null $completed_milestones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCompletedMilestones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCurrentMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereMaxStageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Progression extends Model
{
    use HasFactory;
    protected $fillable = ['max_stage_id', 'current_milestone_id', 'completed_milestones'];
    protected $casts = ['completed_milestones' => 'array'];
}
