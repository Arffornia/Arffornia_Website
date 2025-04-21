@props([
    'title' => 'Technologies',
    'blocks' => [],
])

@php
    $displayedBlocks = count($blocks) > 3 ? array_slice($blocks, 0, 3) : $blocks;
@endphp

<div x-data="{ showAll: false }" class="techno-section">
    <div class="info">
        <p class="title-midle">{{ $title }}</p>
        @if (count($blocks) > 3)
            <button @click="showAll = !showAll" class="medium-btn weak-border">
                {{ __('Voir plus') }}
            </button>
        @endif
    </div>

    <div class="content">
        <template x-for="(block, index) in showAll ? {{ json_encode($blocks) }} : {{ json_encode($displayedBlocks) }}"
            :key="index">
            <div class="block">
                <template x-if="block.link">
                    <a :href="block.link" class="block-link">
                        <div class="img-container">
                            <img :src="block.image" alt="" class="block-image" />
                        </div>
                        <div class="block-text">
                            <p class="title-mini" x-text="block.title"></p>
                            <p class="text-less description" x-text="block.description"></p>
                        </div>
                    </a>
                </template>
                <template x-if="!block.link">
                    <div>
                        <div class="img-container">
                            <img :src="block.image" alt="" class="block-image" />
                        </div>
                        <div class="block-text">
                            <p class="title-mini" x-text="block.title"></p>
                            <p class="text-less description" x-text="block.description"></p>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
