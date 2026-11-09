<?php

namespace App\Services;

use App\Models\MilestoneUnlock;
use App\Models\Recipe;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use ZipArchive;

/**
 * Builds the recipe datapack the mod downloads (Arffornia_Mods#14): Crafter recipes as
 * `arffornia:machine` recipes and every banned recipe disabled with a `neoforge:false` condition.
 * Identical data gives identical bytes, so the sha256 is a stable version and ETag.
 */
class RecipeDatapackService
{
    public const PACK_FORMAT = 48;

    public const CRAFTER = 'arffornia:crafter';

    private const RESOURCE_ID = '/^([a-z0-9_.-]+):([a-z0-9_.\/-]+)$/';

    private const FIXED_MTIME = 315532800;

    /** Stage key of a milestone until milestones get stable keys (spec 017). Must match the mod's CrafterRecipes. */
    public static function milestoneStage(int $milestoneId): string
    {
        return 'arffornia:milestone/' . $milestoneId;
    }

    /** @return array<string, array<mixed>> pack path => JSON body, sorted by path */
    public function files(): array
    {
        $files = [];

        $bans = MilestoneUnlock::pluck('recipes_to_ban')->flatten()->filter()->unique();
        foreach ($bans as $recipeId) {
            $path = $this->recipePath((string) $recipeId);
            if ($path !== null) {
                $files[$path] = ['neoforge:conditions' => [['type' => 'neoforge:false']]];
            }
        }

        $recipes = Recipe::with('milestoneUnlock')->orderBy('id')->get();
        foreach ($recipes as $recipe) {
            $path = $this->recipePath($recipe->type);
            $body = $path !== null ? $this->machineRecipe($recipe) : null;
            if ($body === null) {
                Log::warning('Recipe datapack: skipping unusable recipe', ['id' => $recipe->id, 'type' => $recipe->type]);
                continue;
            }
            $files[$path] = $body;
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /** @return string the zip bytes */
    public function build(): string
    {
        $entries = ['pack.mcmeta' => ['pack' => ['pack_format' => self::PACK_FORMAT, 'description' => 'Arffornia progression recipes']]]
            + $this->files();

        $tmp = tempnam(sys_get_temp_dir(), 'arffornia-datapack');
        $zip = new ZipArchive();
        if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create the datapack archive');
        }
        try {
            foreach ($entries as $name => $body) {
                $zip->addFromString($name, json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
                $zip->setMtimeName($name, self::FIXED_MTIME);
                $zip->setCompressionName($name, ZipArchive::CM_DEFLATE);
            }
            if (! $zip->close()) {
                throw new RuntimeException('Cannot write the datapack archive');
            }

            return (string) file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    private function recipePath(string $recipeId): ?string
    {
        if (! preg_match(self::RESOURCE_ID, $recipeId, $m) || str_contains($m[2], '..')) {
            return null;
        }

        return "data/{$m[1]}/recipe/{$m[2]}.json";
    }

    /** @return array<string, mixed>|null */
    private function machineRecipe(Recipe $recipe): ?array
    {
        $ingredients = [];
        foreach ($recipe->ingredients ?? [] as $ingredient) {
            if (! is_array($ingredient)) {
                continue;
            }
            $key = ! empty($ingredient['tag']) ? 'tag' : 'item';
            $value = $ingredient[$key] ?? null;
            if (! is_string($value) || ! preg_match(self::RESOURCE_ID, $value)) {
                continue;
            }
            $id = $key . '|' . $value;
            $ingredients[$id] ??= ['ingredient' => [$key => $value], 'count' => 0];
            $ingredients[$id]['count'] += max(1, (int) ($ingredient['count'] ?? 1));
        }

        $results = [];
        foreach ($recipe->result ?? [] as $result) {
            if (is_array($result) && is_string($result['item'] ?? null) && preg_match(self::RESOURCE_ID, $result['item'])) {
                $results[] = ['id' => $result['item'], 'count' => max(1, (int) ($result['count'] ?? 1))];
            }
        }

        $milestoneId = $recipe->milestoneUnlock?->milestone_id;
        if ($ingredients === [] || $results === [] || $milestoneId === null) {
            return null;
        }

        $body = [
            'type' => 'arffornia:machine',
            'machine' => self::CRAFTER,
            'stage' => self::milestoneStage($milestoneId),
            'ingredients' => array_values($ingredients),
            'results' => $results,
        ];
        if ((int) $recipe->energy > 0) {
            $body['energy'] = (int) $recipe->energy;
        }
        if ((int) $recipe->time > 0) {
            $body['time'] = (int) $recipe->time;
        }

        return $body;
    }
}
