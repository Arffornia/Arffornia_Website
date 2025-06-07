@props([
    'title' => 'Play',
    'items' => [],
])

<div class="dropdown">
    <div class="mediumPlayBtn">
        {{ $title }} <span class="arrow-down">▼</span>
    </div>
    <div class="dropdown-content">
        @foreach ($items as $item)
            <a href="{{ $item['url'] ?? '#' }}">
                {{ $item['label'] ?? 'Item' }}
            </a>
        @endforeach
    </div>
</div>
