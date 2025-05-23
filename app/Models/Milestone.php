<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $stage_id
 * @property int $reward_progress_points
 * @property int $is_root
 * @property string $icon_type
 * @method static \Database\Factories\MilestoneFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone query()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereIconType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereIsRoot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereRewardProgressPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereStageId($value)
 * @mixin \Eloquent
 */
class Milestone extends Model
{
    use HasFactory;

    public $timestamps = false;
}
