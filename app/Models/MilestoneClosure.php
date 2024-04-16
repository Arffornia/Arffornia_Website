<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneClosure extends Model
{
    use HasFactory;

    protected $table = 'milestone_closure';
    public $timestamps = false;
}
