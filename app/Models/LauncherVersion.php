<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $version
 * @property string $hash
 * @property int $in_prod
 * @property string $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereInProd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherVersion whereVersion($value)
 * @mixin \Eloquent
 */
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
