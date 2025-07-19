@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/shop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/skeleton.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    <div class="shop-wrapper">
        <div class="shop-content-container">

            <div class="shop">
                <div class="shop-section">
                    <p class="section-title">Arrivals:</p>
                    <div class="items-container">
                        @foreach ($newestItems as $item)
                            <div class="shop-item" data-item-id="{{ $item->id }}" title="{{ $item->name }}">
                                <div class="item-container">
                                    <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                    <p class="item-title">{{ $item->name }}</p>
                                    <p class="item-price">{{ $item->real_price }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="shop-section">
                    <p class="section-title">This Week's Deals:</p>
                    <div class="items-container">
                        @foreach ($saleItems as $item)
                            <div class="shop-item" data-item-id="{{ $item->id }}" title="{{ $item->name }}">
                                <div class="item-container">
                                    <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                    <p class="item-title">{{ $item->name }}</p>
                                    <p class="item-price">
                                        <span class="item-price-sale">{{ $item->real_price }}</span>
                                        {{ $item->promo_price }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="shop-section">
                    <p class="section-title">Best Sellers:</p>
                    <div class="items-container">
                        @foreach ($bestSellerItems as $item)
                            <div class="shop-item" data-item-id="{{ $item->id }}" title="{{ $item->name }}">
                                <div class="item-container">
                                    <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                    <p class="item-title">{{ $item->name }}</p>
                                    <p class="item-price">{{ $item->real_price }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="details-panel-container">
                <div id="item-details-panel">

                    <div id="item-details-loader" class="skeleton" style="display: none;">
                        <div class="skeleton-img"
                            style="height: 180px; width: 180px; margin: 0 auto 20px auto; border-radius: 15px;"></div>

                        <div class="details-header-skeleton">
                            <div class="skeleton-line heading" style="width: 60%; margin: 0;"></div>
                            <div class="skeleton-line" style="width: 25%; height: 1.5rem; margin: 0;"></div>
                        </div>

                        <div class="skeleton-line" style="width: 90%; margin-left: 5%; margin-top: 15px;"></div>
                        <div class="skeleton-line" style="width: 80%; margin-left: 10%;"></div>

                        <div class="skeleton-button"
                            style="width: 100%; height: 45px; margin-top: 30px; border-radius: 8px;"></div>
                    </div>


                    <div id="item-details-content" style="display: none;">
                        <img id="details-image" src="" alt="Item Image" />

                        <div class="details-header">
                            <h2 id="details-name"></h2>
                            <p id="details-price"></p>
                        </div>

                        <p id="details-description"></p>

                        <button type="button" id="buy-button" data-item-id=""
                            @guest
disabled
            title="You must be logged in to purchase this item." @endguest>
                            Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.AppData = {
            csrfToken: "{{ csrf_token() }}",
            baseUrl: "{{ url('/') }}",
            isAuth: {{ auth()->check() ? 'true' : 'false' }},
            bestSellerItems: @json($bestSellerItems->pluck('id'))
        };
    </script>
    <script src="{{ asset('js/shop.js') }}"></script>
@endsection
