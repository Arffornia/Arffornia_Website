<?php

namespace App\Models;

use App\Models\Progression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory, SoftDeletes;
    public $incrementing = false; // Using FTB Teams uuid
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'progression_id'];

    public function progression()
    {
        return $this->belongsTo(Progression::class);
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }
}
