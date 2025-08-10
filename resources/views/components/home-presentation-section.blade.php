@props(['imageSrc', 'imageAlt', 'overview', 'title', 'description'])

<div class="home-presentation-section">
    <div class="title-container">
        <p class="title-mini text-less">{{ $overview }}</p>
        <p id="presentation-title" class="title">{{ $title }}</p>
    </div>

    <div class="main-content">
        <div class="image-wrapper slideshow-container">
        </div>
        <div class="description-wrapper">
            <div class="description-content-box">
                {!! $description !!}
            </div>
        </div>
    </div>
</div>
