<?php

namespace App\Progression;

use App\Models\MilestoneRequirement;
use App\Models\MilestoneUnlock;
use Illuminate\Support\Facades\Cache;

/**
 * Item id to the path of its 3D inventory render (Item Icon Exporter output, named by display name),
 * for every item of a published snapshot. Paths are relative to config('progression.icon_base').
 */
class ItemIcons
{
    private const TEXTURES = 'images/item_textures/';

    /** @return array<string, string> */
    public function forSnapshot(int $version, string $etag, array $document): array
    {
        return Cache::rememberForever("progression-icons:{$version}:{$etag}", fn () => $this->build($document));
    }

    /** @return array<string, string> */
    private function build(array $document): array
    {
        $items = [];
        foreach ($document['graphs'] ?? [] as $graph) {
            if (!empty($graph['icon_item'])) {
                $items[$graph['icon_item']] = true;
            }
        }
        foreach ($document['stages'] ?? [] as $stage) {
            foreach ($stage['module'] ?? [] as $entry) {
                $items[$entry['item']] = true;
            }
        }
        foreach ($document['milestones'] ?? [] as $milestone) {
            foreach ($milestone['unlocks'] ?? [] as $unlock) {
                $items[$unlock['item']] = true;
            }
            foreach ($milestone['validation']['items'] ?? [] as $entry) {
                $items[is_array($entry) ? $entry['item'] : $entry] = true;
            }
        }
        $items = array_keys(array_filter($items, fn ($ok, $item) => is_string($item) && !str_starts_with($item, '#') && str_contains($item, ':'), ARRAY_FILTER_USE_BOTH));

        $names = MilestoneUnlock::whereIn('item_id', $items)->whereNotNull('display_name')->pluck('display_name', 'item_id')->all()
            + MilestoneRequirement::whereIn('item_id', $items)->whereNotNull('display_name')->pluck('display_name', 'item_id')->all();
        $paths = MilestoneUnlock::whereIn('item_id', $items)->whereNotNull('image_path')->pluck('image_path', 'item_id')->all()
            + MilestoneRequirement::whereIn('item_id', $items)->whereNotNull('image_path')->pluck('image_path', 'item_id')->all();

        // Renders served from another site (staging points at production) cannot be checked here: the
        // exporter's naming is trusted and the viewer falls back to initials when an image fails.
        $local = !preg_match('#^https?://#', (string) config('progression.icon_base'));

        $icons = [];
        foreach ($items as $item) {
            [$mod, $path] = explode(':', $item, 2);
            $display = $names[$item] ?? implode(' ', array_map('ucfirst', explode('_', basename($path))));
            $candidates = array_values(array_filter([
                $paths[$item] ?? null,
                "{$mod}/{$display}_128x128.png",
                "{$mod}/{$path}_128x128.png",
                "{$mod}_{$path}.png",
            ]));
            if (!$local) {
                $icons[$item] = $candidates[0];
                continue;
            }
            foreach ($candidates as $candidate) {
                if (is_file(public_path(self::TEXTURES . $candidate))) {
                    $icons[$item] = $candidate;
                    break;
                }
            }
        }
        ksort($icons);

        return $icons;
    }
}
