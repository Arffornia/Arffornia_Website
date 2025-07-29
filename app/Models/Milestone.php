<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @property int $x
 * @property int $y
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MilestoneRequirement> $requirements
 * @property-read int|null $requirements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MilestoneUnlock> $unlocks
 * @property-read int|null $unlocks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Milestone whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Milestone whereY($value)
 * @mixin \Eloquent
 */
class Milestone extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'stage_id',
        'reward_progress_points',
        'icon_type',
        'x',
        'y',
    ];

    /**
     * Get the items unlocked by this milestone.
     */
    public function unlocks(): HasMany
    {
        return $this->hasMany(MilestoneUnlock::class);
    }

    /**
     * Get the resources required to unlock this milestone.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(MilestoneRequirement::class);
    }
}
