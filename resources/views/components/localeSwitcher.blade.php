{{-- Language menu: POSTs the choice (CSRF protected), stored in the `locale` cookie.
     resources/js/app.js submits on change; the button is the no-JavaScript fallback. --}}
<form class="locale-switcher" action="{{ route('locale.switch') }}" method="POST">
    @csrf
    <select name="locale" class="locale-switcher__select" aria-label="{{ __('common.language') }}">
        @foreach (\App\Http\Middleware\SetLocale::supported() as $supportedLocale)
            <option value="{{ $supportedLocale }}" lang="{{ $supportedLocale }}" @selected(app()->getLocale() === $supportedLocale)>{{ __('common.languages.' . $supportedLocale) }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="locale-switcher__submit">OK</button></noscript>
</form>
