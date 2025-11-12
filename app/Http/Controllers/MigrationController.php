<?php

namespace App\Http\Controllers;

use App\Models\MilestoneUnlock;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class MigrationController extends Controller
{
    /**
     * Identifies and returns the item_ids of unlocks that have banned recipes
     * but do not yet have a corresponding custom recipe.
     *
     * @return JsonResponse
     */
    public function getItemsToMigrate(): JsonResponse
    {
        $itemIds = MilestoneUnlock::query()
            // Select unlocks that have at least one recipe to ban.
            ->whereJsonLength('recipes_to_ban', '>', 0)
            // Filter out unlocks that already have an associated recipe.
            ->whereDoesntHave('recipe')
            ->pluck('item_id');

        return response()->json($itemIds);
    }

    /**
     * Receives a batch of recipes from the game client and creates them in the database.
     * This is a transactional operation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitBatchRecipes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipes' => 'required|array',
            'recipes.*.item_id' => 'required|string|exists:milestone_unlocks,item_id',
            'recipes.*.ingredients' => 'required|array',
            'recipes.*.result' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $recipesToProcess = $validator->validated()['recipes'];
        $createdCount = 0;

        DB::beginTransaction();
        try {
            foreach ($recipesToProcess as $recipeData) {
                $unlock = MilestoneUnlock::where('item_id', $recipeData['item_id'])->first();

                if (!$unlock) {
                    Log::warning('Migration: Received recipe for an item_id with no corresponding unlock.', ['item_id' => $recipeData['item_id']]);
                    continue;
                }

                $recipeType = 'arffornia:' . str_replace(':', '_', $unlock->item_id) . '_recipe';

                Recipe::updateOrCreate(
                    ['milestone_unlock_id' => $unlock->id],
                    [
                        'type' => $recipeType,
                        'ingredients' => $recipeData['ingredients'],
                        'result' => $recipeData['result'],
                        'energy' => $recipeData['energy'] ?? 0,
                        'time' => $recipeData['time'] ?? 0,
                    ]
                );
                $createdCount++;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch recipe migration failed.', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during the batch recipe submission.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['message' => "Successfully processed {$createdCount} recipes."]);
    }
}
