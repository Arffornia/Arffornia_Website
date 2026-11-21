<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressionGraph extends Model
{
    protected $fillable = ['id', 'name', 'icon_item_id', 'categories', 'key', 'mod_id', 'branch', 'name_fr'];
    protected $casts = ['categories' => 'array'];

    protected static function booted(): void
    {
        static::creating(function (ProgressionGraph $graph) {
            if ($graph->key === null && $graph->name !== null) {
                $graph->key = \App\Progression\Keys::forNewGraph($graph->name);
            }
        });
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class, 'graph_id');
    }
}
