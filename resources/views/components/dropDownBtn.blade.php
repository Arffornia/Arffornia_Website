@props([
    'title' => null,
    'items' => [],
])

<div class="dropdown">
    <div class="mediumPlayBtn">
        {{ $title ?? __('home.about.play') }} <span class="arrow-down">▼</span>
    </div>
    <div class="dropdown-content">
        @foreach ($items as $item)
            <a href="{{ $item['url'] ?? '#' }}">
                {{ $item['label'] ?? '' }}
            </a>
        @endforeach
    </div>
</div>
