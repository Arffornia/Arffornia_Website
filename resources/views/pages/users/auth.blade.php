<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('profile.minecraft.title') }}</title>
</head>

<body>
    <h1>{{ __('profile.minecraft.title') }}</h1>
    <p>{{ __('profile.minecraft.uuid', ['value' => $uuid]) }}</p>
    <p>{{ __('profile.minecraft.username', ['value' => $username]) }}</p>
    <p>{{ __('profile.minecraft.skin', ['value' => $skinUrl]) }}</p>
    <p>{{ __('profile.minecraft.cape', ['value' => $capeUrl]) }}</p>
</body>

</html>
