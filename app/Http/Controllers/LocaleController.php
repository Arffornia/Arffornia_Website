<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Store the visitor's language choice in a cookie (one year) and go back to the previous page.
     */
    public function switch(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::supported())],
        ])['locale'];

        return redirect()->back(fallback: '/')
            ->withCookie(cookie(SetLocale::COOKIE, $locale, 60 * 24 * 365));
    }
}
