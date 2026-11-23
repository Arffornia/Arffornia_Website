<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/progression-embed.css') }}">
    @vite(['resources/js/progression-embed.ts'])
    <title>{{ __('progression.title') }}</title>
</head>

<body>
    <main id="progression-root" aria-label="{{ __('progression.title') }}">
        <p data-loading>{{ __('progression.loading') }}</p>
        <p data-error hidden>{{ __('progression.error') }}</p>
        <p data-message hidden></p>
    </main>
    <script>
        window.ArfforniaProgression = @json($config);
    </script>
</body>

</html>
