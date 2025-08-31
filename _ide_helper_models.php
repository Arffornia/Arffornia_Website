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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereInProd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $player_name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LauncherImage wherePlayerName($value)
 */
	class LauncherImage extends \Eloquent {}
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
 * @property int $x
 * @property int $y
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MilestoneRequirement> $requirements
 * @property-read int|null $requirements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MilestoneUnlock> $unlocks
 * @property-read int|null $unlocks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Milestone whereX($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Milestone whereY($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Stage|null $stage
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
 * @property int $id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneClosure whereId($value)
 * @mixin \Eloquent
 */
	class MilestoneClosure extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $milestone_id
 * @property string $item_id
 * @property int $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string|null $display_name
 * @property string|null $image_path
 * @property-read string|null $image_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneRequirement whereImagePath($value)
 */
	class MilestoneRequirement extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $milestone_id
 * @property string $item_id
 * @property string|null $display_name
 * @property string $recipe_id_to_ban
 * @property int|null $shop_price
 * @property string|null $image_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $image_url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereRecipeIdToBan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereShopPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUnlock whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Recipe|null $recipe
 */
	class MilestoneUnlock extends \Eloquent {}
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
 * @property int $id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MilestoneUser whereId($value)
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
 * @property int $user_id
 * @property int $shop_item_id
 * @property array<array-key, mixed> $commands
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereCommands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereShopItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PendingReward whereUserId($value)
 * @mixin \Eloquent
 */
	class PendingReward extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $max_stage_id
 * @property int|null $current_milestone_id
 * @property array<array-key, mixed>|null $completed_milestones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCompletedMilestones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereCurrentMilestoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereMaxStageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Progression whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Milestone|null $currentTargetedMilestone
 * @property-read \App\Models\Stage $maxStage
 */
	class Progression extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $type
 * @property array<array-key, mixed> $ingredients
 * @property array<array-key, mixed> $result
 * @property int|null $energy
 * @property int|null $time
 * @property int $milestone_unlock_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MilestoneUnlock $milestoneUnlock
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereEnergy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereIngredients($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereMilestoneUnlockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUpdatedAt($value)
 */
	class Recipe extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name A human-readable name for the service account.
 * @property string $client_id The public identifier for the service account.
 * @property string $client_secret The hashed secret for the service account.
 * @property string $roles Comma-separated list of roles/permissions.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServiceAccount whereUpdatedAt($value)
 */
	class ServiceAccount extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $img_url
 * @property int $category_id
 * @property int $real_price
 * @property int $promo_price
 * @property bool $is_unique
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserSale> $userSales
 * @property-read int|null $user_sales_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereImgUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem wherePromoPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereRealPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereUpdatedAt($value)
 * @property array<array-key, mixed>|null $commands
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCommands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereIsUnique($value)
 * @mixin \Eloquent
 */
	class ShopItem extends \Eloquent {}
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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Milestone> $milestones
 * @property-read int|null $milestones_count
 */
	class Stage extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property string $id
 * @property string $name
 * @property int $progression_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read Progression $progression
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereProgressionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withoutTrashed()
 * @mixin \Eloquent
 */
	class Team extends \Eloquent {}
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
 * @property string $uuid
 * @property string|null $role
 * @property string $grade
 * @property int $day_streak
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDayStreak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUuid($value)
 * @property int|null $solo_progression_id
 * @property int|null $active_progression_id
 * @property string|null $team_id
 * @property-read \App\Models\Progression|null $activeProgression
 * @property-read \App\Models\Progression|null $soloProgression
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActiveProgressionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSoloProgressionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTeamId($value)
 * @mixin \Eloquent
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $shop_item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereShopItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSale whereUserId($value)
 * @mixin \Eloquent
 */
	class UserSale extends \Eloquent {}
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

