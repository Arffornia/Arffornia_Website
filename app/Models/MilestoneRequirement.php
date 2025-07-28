<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 */
class MilestoneRequirement extends Model
{
    use HasFactory;
    protected $fillable = ['milestone_id', 'item_id', 'display_name', 'image_path', 'amount'];

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
}
