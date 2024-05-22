<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LauncherVersion extends Model
{
    use HasFactory;

    protected $table = 'launcher_version';

    protected $fillable = [
        'version',
        'hash',
        'in_prod',
        'path',
    ];

}
