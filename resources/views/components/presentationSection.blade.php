@props([
    'imageSrc',
    'imageAlt',
    'overview',
    'title',
    'description',
    'dir' => 'left'
])

@php
    $isRight = $dir === 'right';
@endphp

<div class="presentation-section">
    <div class="overview {{ $isRight ? 'flex-row-reverse' : '' }}">
        <img class="overview-image" src="{{ $imageSrc }}" alt="{{ $imageAlt }}" />
        <div class="section-ti-sep">
            <p class="title-mini text-less">{{ $overview }}</p>
            <p id="presentation-title" class="title">{{ $title }}</p>
            {!! $description !!}
        </div>
    </div>
</div>
