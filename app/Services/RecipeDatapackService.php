<?php

namespace App\Services;

use App\Models\MilestoneUnlock;
use App\Models\Recipe;
use Illuminate\Support\Facades\Cache;
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

    private const NAMESPACE = '[a-z0-9_.-]+';

    private const PATH_SEGMENT = '[a-z0-9_.-]+';

    private const MAX_STACK = 99;

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

    /**
     * The zip and its sha256, cached until a recipe or an unlock changes. The short TTL bounds two edits
     * landing in the same second, which the key cannot tell apart.
     *
     * @return array{0: string, 1: string}
     */
    public function current(): array
    {
        $key = 'recipe-datapack:' . sha1(json_encode([
            Recipe::count(), Recipe::max('updated_at'), Recipe::max('id'),
            MilestoneUnlock::count(), MilestoneUnlock::max('updated_at'), MilestoneUnlock::max('id'),
        ]));

        return Cache::remember($key, now()->addMinutes(5), function () {
            $zip = $this->build();

            return [$zip, hash('sha256', $zip)];
        });
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

    /** A `namespace:path` id the mod accepts: no `..` anywhere, no empty or `.` segment. */
    public static function isResourceId(string $id): bool
    {
        $pattern = '/^' . self::NAMESPACE . ':' . self::PATH_SEGMENT . '(\\/' . self::PATH_SEGMENT . ')*$/';
        if (! preg_match($pattern, $id) || str_contains($id, '..')) {
            return false;
        }
        [$namespace, $path] = explode(':', $id, 2);

        return $namespace !== '.' && ! in_array('.', explode('/', $path), true);
    }

    private function recipePath(string $recipeId): ?string
    {
        if (! self::isResourceId($recipeId)) {
            return null;
        }
        [$namespace, $path] = explode(':', $recipeId, 2);

        return "data/{$namespace}/recipe/{$path}.json";
    }

    /** @return array<string, mixed>|null */
    private function machineRecipe(Recipe $recipe): ?array
    {
        $ingredients = [];
        foreach ($recipe->ingredients ?? [] as $ingredient) {
            if ($ingredient === null) {
                continue;
            }
            $key = is_array($ingredient) && ! empty($ingredient['tag']) ? 'tag' : 'item';
            $value = is_array($ingredient) ? ($ingredient[$key] ?? null) : null;
            if (! is_string($value) || ! self::isResourceId($value)) {
                return null;
            }
            $id = $key . '|' . $value;
            $ingredients[$id] ??= ['ingredient' => [$key => $value], 'count' => 0];
            $ingredients[$id]['count'] += max(1, (int) ($ingredient['count'] ?? 1));
        }

        $results = [];
        foreach ($recipe->result ?? [] as $result) {
            $count = is_array($result) ? (int) ($result['count'] ?? 1) : 0;
            if (! is_array($result) || ! is_string($result['item'] ?? null) || ! self::isResourceId($result['item'])
                || $count < 1 || $count > self::MAX_STACK) {
                return null;
            }
            $results[] = ['id' => $result['item'], 'count' => $count];
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
