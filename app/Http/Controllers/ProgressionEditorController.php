<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Spec 019: the admin progression editor page; everything it edits goes through the draft API. */
class ProgressionEditorController extends Controller
{
    public function editorView(): View
    {
        return view('admin.progression_editor', [
            'config' => [
                'api' => url('/api/progression/drafts'),
                'csrf' => csrf_token(),
                'iconsUrl' => url('/icons/manifest.json'),
                'iconBase' => url('/icons/128') . '/',
                'locale' => app()->getLocale() === 'fr' ? 'fr' : 'en',
                'i18n' => __('progression_editor.js'),
            ],
        ]);
    }
}
