@props([
    'title' => 'Technologies',
    'blocks' => [],
    'limit' => 3,
    'showAll' => false,
])

@php
    $total = count($blocks);
    $displayedBlocks = $showAll ? $blocks : array_slice($blocks, 0, $limit);
@endphp

<div class="techno-section">
    <div class="info">
        <p class="title-midle">{{ $title }}</p>

        @if ($total > $limit)
            <a href="?showAll=1" class="medium-btn weak-border">
                {{ request('showAll') ? 'Voir moins' : 'Voir plus' }}
            </a>
        @endif
    </div>

    <div class="content">
        @foreach ($displayedBlocks as $block)
            @php
                $hasLink = !empty($block['link']);
            @endphp

            @if ($hasLink)
                <a href="{{ $block['link'] }}" class="block-link">
            @endif

            <div class="block">
                <div class="img-container">
                    <img src="{{ $block['image'] }}" alt="{{ $block['title'] }}" class="block-image" />
                </div>
                <div class="block-text">
                    <p class="title-mini">{{ $block['title'] }}</p>
                    <p class="text-less description">{{ $block['description'] }}</p>
                </div>
            </div>

            @if ($hasLink)
                </a>
            @endif
        @endforeach
    </div>
</div>
