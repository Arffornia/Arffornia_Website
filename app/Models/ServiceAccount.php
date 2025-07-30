<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ServiceAccount extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'roles',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * Get user's role as an array of roles.
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        return explode(',', $this->roles);
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
