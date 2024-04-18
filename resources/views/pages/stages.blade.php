@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{asset("css/pages/stages.css")}}">
@endsection

@section('content')
    <div class="bg">
        <div class="info info-hidden">
            <div class="closeBtn">
                <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#ffffff" d="M9.172 16.242 12 13.414l2.828 2.828 1.414-1.414L13.414 12l2.828-2.828-1.414-1.414L12 10.586 9.172 7.758 7.758 9.172 10.586 12l-2.828 2.828z"/><path fill="#ffffff" d="M12 22c5.514 0 10-4.486 10-10S17.514 2 12 2 2 6.486 2 12s4.486 10 10 10zm0-18c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8z"/></svg>
            </div>

            <div class="titleContainer">
                <div class="iconContainer">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6h96 32H424c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/></svg>
                    </div>
                </div>
                <div id="title">Mekanism Factory Tier 1</div>
            </div>

            <div class="textContainer">
                <p><span class="textTitle">Description: </span><span id="description">Basic of meka</span></p>
                <p><span class="textTitle">Stage: </span><span id="stageNumber">5</span></p>
                <p><span class="textTitle">Points: </span><span id="reward_progress_points">20000</span></p>
                <p class="textTitle">Items:</p>
            </div>

            <div id="itemsContainer">
                <ul>
                    <li>Smelt factory mk1</li>
                    <li>Osmium</li>
                    <li>Osmium</li>
                </ul>
            </div>
        </div>
        <div class="canvas">
            
        </div>
    </div>   
@endsection
@section('script')
    <script>
        const stages = @json($stages);
        const milestones = @json($milestones);
        const milestone_closure = @json($milestone_closure);
    </script>

    <script src="{{asset("js/mod_icons.js")}}"></script>
    <script src="{{asset("js/stages.js")}}"></script>

@endsection