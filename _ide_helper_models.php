<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $path
 * @property int $in_prod
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage query()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage whereInProd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherImage whereUpdatedAt($value)
 */
	class LauncherImage extends \Eloquent {}
}

namespace App\Models{
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
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereInProd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LauncherVersion whereVersion($value)
 */
	class LauncherVersion extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $stage_id
 * @property int $reward_progress_points
 * @property int $is_root
 * @property string $icon_type
 * @method static \Database\Factories\MilestoneFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone query()
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereIconType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereIsRoot($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereRewardProgressPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Milestone whereStageId($value)
 * @mixin \Eloquent
 */
	class Milestone extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $milestone_id
 * @property int $descendant_id
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure query()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure whereDescendantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneClosure whereMilestoneId($value)
 * @mixin \Eloquent
 */
	class MilestoneClosure extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $milestone_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Milestone|null $milestone
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\MilestoneUserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MilestoneUser whereUserId($value)
 * @mixin \Eloquent
 */
	class MilestoneUser extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $imgUrl
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|News query()
 * @method static \Illuminate\Database\Eloquent\Builder|News whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereImgUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|News whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	class News extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $number
 * @property string $name
 * @property string $description
 * @property int $reward_progress_points
 * @method static \Database\Factories\StageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Stage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage query()
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Stage whereRewardProgressPoints($value)
 * @mixin \Eloquent
 */
	class Stage extends \Eloquent {}
}

namespace App\Models{
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
 * @mixin \Eloquent
 * @property string $uuid
 * @property string|null $role
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUuid($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\VoteFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Vote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vote query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vote whereUserId($value)
 * @mixin \Eloquent
 */
	class Vote extends \Eloquent {}
}

