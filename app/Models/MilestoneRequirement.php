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
            // Encode each segment separately to handle spaces and special chars
            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $this->image_path)));
            return asset('images/item_textures/' . $encodedPath);
        }

        if ($this->item_id) {
            $parts = explode(':', $this->item_id);
            if (count($parts) === 2) {
                $modId = $parts[0];
                $itemName = $parts[1];
                // Use the display_name already stored in DB (e.g. "Osmium Ingot" for mekanism:ingot_osmium).
                // Fall back to ucfirst-based computation only if display_name is not set.
                $displayName = $this->display_name
                    ?? implode(' ', array_map('ucfirst', explode('_', $itemName)));

                $candidates = [
                    // Display name — subfolder (actual exporter output format, checked first)
                    "{$modId}/{$displayName}_128x128.png",
                    "{$modId}/{$displayName}_36x36.png",
                    "{$modId}/{$displayName}.png",
                    // Registry name — flat & subfolder (fallback)
                    "{$modId}/{$itemName}_128x128.png",
                    "{$modId}/{$itemName}_36x36.png",
                    "{$modId}_{$itemName}_128x128.png",
                    "{$modId}_{$itemName}_36x36.png",
                    "{$modId}/{$itemName}.png",
                    "{$modId}_{$itemName}.png",
                ];

                foreach ($candidates as $candidate) {
                    if (file_exists(public_path('images/item_textures/' . $candidate))) {
                        // Encode each path segment to produce a valid URL (handles spaces, etc.)
                        $encodedCandidate = implode('/', array_map('rawurlencode', explode('/', $candidate)));
                        return asset('images/item_textures/' . $encodedCandidate);
                    }
                }
            }
        }

        // Fallback image
        return asset('images/Crafting_Table.png');
    }
}
