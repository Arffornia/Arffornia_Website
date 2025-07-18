@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/shop.css') }}">
@endsection

@section('content')
    <div class="shop-wrapper">
        <div class="shop">
            <div class="shop-section">
                <p class="section-title">Arrivals: </p>
                <div class="items-container">
                    @foreach ($newestItems as $item)
                        <a href="{{ $item->img_url }}" class="shop-item" title="See on Website">
                            <div class="item-container">
                                <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                <p class="item-title">{{ $item->name }}</p>
                                <p class="item-price">{{ $item->real_price }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="shop-section">
                <p class="section-title">This Week's Deals: </p>
                <div class="items-container">
                    @foreach ($saleItems as $item)
                        <a href="{{ $item->img_url }}" class="shop-item" title="See on Website">
                            <div class="item-container">
                                <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                <p class="item-title">{{ $item->name }}</p>
                                <p class="item-price">
                                    <span class="item-price-sale">
                                        {{ $item->real_price }}
                                    </span>
                                    {{ $item->promo_price }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="shop-section">
                <p class="section-title">Best Sellers: </p>
                <div class="items-container">
                    @foreach ($bestSellerItems as $item)
                        <a href="{{ $item->img_url }}" class="shop-item" title="See on Website">
                            <div class="item-container">
                                <img class="item-icon" src="{{ $item->img_url }}" alt="{{ $item->name }}" />
                                <p class="item-title">{{ $item->name }}</p>
                                <p class="item-price">{{ $item->real_price }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
