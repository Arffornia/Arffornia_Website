{{-- EN/FR switcher: POSTs the choice (CSRF protected), stored in the `locale` cookie --}}
<form class="locale-switcher" action="{{ route('locale.switch') }}" method="POST" aria-label="{{ __('common.language') }}">
    @csrf
    @foreach (\App\Http\Middleware\SetLocale::supported() as $supportedLocale)
        <button type="submit" name="locale" value="{{ $supportedLocale }}" lang="{{ $supportedLocale }}"
            title="{{ __('common.switch_to', ['language' => __('common.languages.' . $supportedLocale)]) }}"
            @class(['locale-switcher__btn', 'is-active' => app()->getLocale() === $supportedLocale])
            @if (app()->getLocale() === $supportedLocale) aria-current="true" @endif>{{ strtoupper($supportedLocale) }}</button>
    @endforeach
</form>
