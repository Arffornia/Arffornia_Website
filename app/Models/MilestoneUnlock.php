<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 */
class MilestoneUnlock extends Model
{
    use HasFactory;
    protected $fillable = ['milestone_id', 'item_id', 'display_name', 'recipe_id_to_ban', 'shop_price', 'image_path'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Get the full URL to the item's image.
     *
     * @return string|null
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return asset('images/item_textures/' . $this->image_path);
        }

        // Fallback image
        return asset('images/Crafting_Table.png');
    }

    /**
     * Get the recipe associated with the milestone unlock.
     */
    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }
}
