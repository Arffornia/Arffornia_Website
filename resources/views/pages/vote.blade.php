@extends('layout')

@section('extraHead')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/pages/vote.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/presentationSection.css') }}">
@endsection

@section('content')
    <div class="vote-wrapper">
        <div class="vote-container">
            {{-- Left Column: Voting Sites --}}
            <div class="vote-sites-column">
                <div class="sites-section">
                    <div class="info">
                        <p class="title-midle">Voting Sites</p>
                    </div>
                    <div class="content">
                        @auth
                            @forelse ($votingSites as $key => $site)
                                <div class="site-card">
                                    <div class="img-container">
                                        <img src="{{ asset('images/join_us.png') }}" alt="{{ $site['name'] }}"
                                            class="block-image" />
                                    </div>
                                    <div class="block-text">
                                        <p class="title-mini">{{ $site['name'] }}</p>
                                        <p class="cooldown-text">Every {{ $site['cooldown_hours'] }} hours</p>
                                    </div>
                                    <div class="actions">
                                        <a href="{{ str_replace('{player}', auth()->user()->name, $site['url']) }}"
                                            target="_blank" class="action-btn" title="Open voting site">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M10 6v2h2.59L5 15.59 6.41 17 14 9.41V12h2V6h-6z" />
                                            </svg>
                                        </a>
                                        <button class="action-btn verify-btn" data-site-key="{{ $key }}"
                                            title="Verify my vote">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p style="color:white;">No voting sites are configured.</p>
                            @endforelse
                        @else
                            <p style="color:white;">You must be logged in to vote.</p>
                        @endauth
                    </div>
                </div>
            </div>

            {{-- Right Column: Leaderboards --}}
            <div class="leaderboards-column">
                <div class="leaderboard">
                    <h3>This Month's Leaderboard</h3>
                    @if ($monthlyTop->isNotEmpty())
                        <ol>
                            @foreach ($monthlyTop as $user)
                                <li>
                                    <span class="player-name">{{ $loop->iteration }}. {{ $user->name }}</span>
                                    <span class="vote-count">{{ $user->votes_count }} votes</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p>No votes recorded this month yet.</p>
                    @endif
                </div>

                <div class="leaderboard">
                    <h3>All-Time Leaderboard</h3>
                    @if ($allTimeTop->isNotEmpty())
                        <ol>
                            @foreach ($allTimeTop as $user)
                                <li>
                                    <span class="player-name">{{ $loop->iteration }}. {{ $user->name }}</span>
                                    <span class="vote-count">{{ $user->votes_count }} votes</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p>No votes recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @vite(['resources/js/vote.js'])
@endsection
