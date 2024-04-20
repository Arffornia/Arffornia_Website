<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $milestone_id
 * @property int $descendant_id
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure query()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure whereDescendantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure whereMilestoneId($value)
 * @mixin \Eloquent
 */
class MilestoneClosure extends Model
{
    use HasFactory;

    protected $table = 'milestone_closure';
    public $timestamps = false;
}
