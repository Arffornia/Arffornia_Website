<?php

namespace App\Progression;

use App\Models\Progression;
use App\Models\ServiceAccount;
use App\Models\User;

/**
 * Spec 019 Q4: progress is public by default; a private progression is shown only to its members,
 * admins and the game server. Every route that serves a progression asks here.
 */
class ProgressionVisibility
{
    public function viewer(): User|ServiceAccount|null
    {
        $viewer = auth('sanctum')->user() ?? auth('web')->user();

        return $viewer instanceof User || $viewer instanceof ServiceAccount ? $viewer : null;
    }

    public function canSee(?Progression $progression): bool
    {
        if ($progression === null || $progression->is_public !== false) {
            return true;
        }
        $viewer = $this->viewer();
        if ($viewer instanceof ServiceAccount) {
            return $viewer->hasAnyRole(['admin', 'progression_editor']);
        }

        return $viewer instanceof User && ($viewer->hasRole('admin') || self::isMember($viewer, $progression));
    }

    public static function isMember(User $user, Progression $progression): bool
    {
        return (int) $user->active_progression_id === $progression->id || (int) $user->solo_progression_id === $progression->id;
    }
}
