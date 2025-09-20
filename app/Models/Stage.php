<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $number
 * @property string $name
 * @property string $description
 * @property int $reward_progress_points
 * @method static \Database\Factories\StageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Stage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage query()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereRewardProgressPoints($value)
 * @mixin \Eloquent
 */
class Stage extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = ['number', 'name', 'description', 'reward_progress_points'];

    /**
     * Get the milestones for the stage.
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    /**
     * Get the progressions that have this stage as their maximum unlocked stage.
     */
    public function progressions(): HasMany
    {
        return $this->hasMany(Progression::class, 'max_stage_id');
    }
}
