<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressionGraph extends Model
{
    protected $fillable = ['name', 'icon_item_id', 'categories'];
    protected $casts = ['categories' => 'array'];

    public function milestones()
    {
        return $this->hasMany(Milestone::class, 'graph_id');
    }
}
