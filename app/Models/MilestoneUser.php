<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $milestone_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Milestone|null $milestone
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\MilestoneUserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereUserId($value)
 * @property int $id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUser whereId($value)
 * @mixin \Eloquent
 */
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
