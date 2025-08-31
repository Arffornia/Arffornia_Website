<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property string $path
 * @property int $in_prod
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereInProd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LauncherImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'in_prod',
        'player_name',
    ];
}
