<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 *
 *
 * @property int $id
 * @property string $name
 * @property mixed $password
 * @property string|null $email
 * @property float $money
 * @property int $progress_point
 * @property int $stage_id
 * @property string|null $last_connexion
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vote> $votes
 * @property-read int|null $votes_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastConnexion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMoney($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProgressPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @property string $uuid
 * @property string|null $role
 * @property string $grade
 * @property int $day_streak
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDayStreak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUuid($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'uuid',
        'money',
        'progress_point',
        'stage_id',
        'day_streak',
        'grade',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function getVoteCount()
    {
        return $this->votes()->count();
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function soloProgression()
    {
        return $this->belongsTo(Progression::class, 'solo_progression_id');
    }

    public function activeProgression()
    {
        return $this->belongsTo(Progression::class, 'active_progression_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get user's role as an array of roles.
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        return explode(',', $this->role);
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Check if the user has any of the specified roles.
     *
     * @param array<string> $rolesToCheck
     * @return bool
     */
    public function hasAnyRole(array $rolesToCheck): bool
    {
        return !empty(array_intersect($this->getRoles(), $rolesToCheck));
    }

    /**
     * Check if the user has all of the specified roles.
     *
     * @param array<string> $requiredRoles
     * @return bool
     */
    public function hasAllRoles(array $requiredRoles): bool
    {
        return empty(array_diff($requiredRoles, $this->getRoles()));
    }
}
