<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\MilestoneUnlock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RecipeController extends Controller
{
    /**
     * Get all custom recipes.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(Recipe::all());
    }

    /**
     * Get a single recipe by its type.
     *
     * @param string $type
     * @return JsonResponse
     */
    public function showByType(string $type): JsonResponse
    {
        $recipe = Recipe::where('type', $type)->first();

        if (!$recipe) {
            return response()->json(['message' => 'Recipe not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($recipe);
    }

    /**
     * Store or update a recipe for a milestone unlock.
     *
     * @param Request $request
     * @param MilestoneUnlock $unlock
     * @return JsonResponse
     */
    public function storeOrUpdate(Request $request, MilestoneUnlock $unlock): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ingredients' => 'required|array',
            'ingredients.*.item' => 'required_without:ingredients.*.tag|string',
            'ingredients.*.tag' => 'required_without:ingredients.*.item|string',
            'ingredients.*.count' => 'sometimes|integer|min:1',
            'result' => 'required|array|min:1',
            'result.*.item' => 'required|string',
            'result.*.count' => 'sometimes|integer|min:1',

            'energy' => 'nullable|integer|min:0',
            'time' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validatedData = $validator->validated();
        $recipeType = 'arffornia:' . str_replace(':', '_', $unlock->item_id) . '_recipe';

        $recipe = Recipe::updateOrCreate(
            ['milestone_unlock_id' => $unlock->id],
            [
                'type' => $recipeType,
                'ingredients' => $validatedData['ingredients'],
                'result' => $validatedData['result'],
                'energy' => $validatedData['energy'],
                'time' => $validatedData['time'],
            ]
        );

        return response()->json($recipe, Response::HTTP_OK);
    }
}
