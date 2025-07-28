<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $milestone_id
 * @property string $item_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MilestoneRequirement extends Model
{
    use HasFactory;
    protected $fillable = ['milestone_id', 'item_id', 'amount'];
}
