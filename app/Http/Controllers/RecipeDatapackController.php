<?php

namespace App\Http\Controllers;

use App\Services\RecipeDatapackService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecipeDatapackController extends Controller
{
    public function __construct(private readonly RecipeDatapackService $datapacks)
    {
    }

    /** The recipe datapack as a zip; the ETag is the sha256 of the body so the mod can verify and skip unchanged packs. */
    public function show(Request $request): Response
    {
        $zip = $this->datapacks->build();
        $etag = '"' . hash('sha256', $zip) . '"';

        if (in_array($etag, $request->getETags(), true)) {
            return response('', Response::HTTP_NOT_MODIFIED)->setEtag(trim($etag, '"'));
        }

        return response($zip, Response::HTTP_OK, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="arffornia-recipes.zip"',
        ])->setEtag(trim($etag, '"'));
    }
}
