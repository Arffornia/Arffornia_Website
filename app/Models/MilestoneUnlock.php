<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
