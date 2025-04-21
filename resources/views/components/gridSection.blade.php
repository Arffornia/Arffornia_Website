@props(['title', 'description', 'blocks' => []])

<div class="grid-section">
    <p id="grid-title" class="title">{{ $title }}</p>
    <p id="grid-description" class="text-less title-mini">{{ $description }}</p>

    <div class="grids">
        @foreach ($blocks as $block)
            <div class="test-chamber">
                @if (!empty($block['link']))
                    <a href="{{ $block['link'] }}">
                        <div class="image-container">
                            <img src="{{ $block['image'] }}" alt="{{ $block['title'] }}" />
                            <div class="overlay">
                                <p id="block-title" class="title-mini text-less">{{ $block['title'] }}</p>
                                <p id="block-description" class="title-midle">{{ $block['description'] }}</p>
                            </div>
                        </div>
                    </a>
                @else
                    <div class="image-container">
                        <img src="{{ $block['image'] }}" alt="{{ $block['title'] }}" />
                        <div class="overlay">
                            <p id="block-title" class="title-mini text-less">{{ $block['title'] }}</p>
                            <p id="block-description" class="title-midle">{{ $block['description'] }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
