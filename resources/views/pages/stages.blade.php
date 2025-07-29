@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/stages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/skeleton.css') }}">
@endsection

@section('content')
    <div class="bg">
        <div class="info info-hidden">
            <div class="closeBtn">
                <svg fill="#000000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#ffffff"
                        d="M9.172 16.242 12 13.414l2.828 2.828 1.414-1.414L13.414 12l2.828-2.828-1.414-1.414L12 10.586 9.172 7.758 7.758 9.172 10.586 12l-2.828 2.828z" />
                    <path fill="#ffffff"
                        d="M12 22c5.514 0 10-4.486 10-10S17.514 2 12 2 2 6.486 2 12s4.486 10 10 10zm0-18c4.411 0 8 3.589 8 8s-3.589 8-8 8-8-3.589-8-8 3.589-8 8-8z" />
                </svg>
            </div>

            <div id="info-loader" class="skeleton">
                <div class="titleContainer">
                    <div class="iconContainer">
                        <div class="icon">
                            <div class="skeleton-avatar"></div>
                        </div>
                    </div>
                    <div class="skeleton-line heading" style="width: 60%; margin-left: 3%"></div>
                </div>


                <div class="textContainer">
                    <div class="skeleton-line" style="width: 70%"></div>
                    <div class="skeleton-line" style="width: 20%"></div>
                    <div class="skeleton-line" style="width: 30%"></div>
                </div>

                <div id="itemsContainer">
                    <div class="skeleton-img" style="height: 70px"></div>
                </div>
            </div>

            <div id="info-content" style="display: none;">
                <div class="titleContainer">
                    <div class="iconContainer">
                        <div class="icon" id="iconContent"></div>
                    </div>
                    <div id="milestone-title" class="title"></div>
                </div>
                <div class="textContainer">
                    <p><span class="textTitle">Description: </span><span id="description" class="textDescription"></span>
                    </p>
                    <p><span class="textTitle">Stage: </span><span id="stageNumber" class="textDescription"></span></p>
                    <p><span class="textTitle">Points: </span><span id="reward_progress_points"
                            class="textDescription"></span></p>
                    <p class="textTitle">New items:</p>
                </div>
                <div id="newItemsContainer">
                    <ul></ul>
                </div>
                <div class="textContainer">
                    <p class="textTitle">Required items:</p>
                </div>
                <div id="requiredItemsContainer">
                    <ul></ul>
                </div>


                @if ($isAdmin ?? false)
                    <div class="admin-actions">
                        <button id="editBtn">Edit</button>
                        <button id="saveBtn" style="display: none;">Save</button>
                        <button id="cancelBtn" style="display: none;">Cancel</button>
                    </div>
                @endif
            </div>
        </div>

        @if ($isAdmin ?? false)
            <div class="admin-panel">
                <button id="exportStagesBtn" class="export-btn">Export Stages</button>
                <div class="admin-controls">
                    <p>Mode:</p>
                    <button class="admin-mode-btn active" data-mode="view">View</button>
                    <button class="admin-mode-btn" data-mode="add">Add</button>
                    <button class="admin-mode-btn" data-mode="delete">Delete</button>
                    <button class="admin-mode-btn" data-mode="link">Link</button>
                    <button class="admin-mode-btn" data-mode="unlink">Unlink</button>
                    <button class="admin-mode-btn" data-mode="move">Move</button>
                </div>
            </div>
        @endif

        <div class="canvas"></div>
    </div>
@endsection
@section('script')
    <script>
        window.AppData = {
            stages: @json($stages),
            milestones: @json($milestones),
            milestone_closure: @json($milestone_closure),
            isAdmin: @json($isAdmin ?? false),
            csrfToken: "{{ csrf_token() }}",
            baseUrl: "{{ url('/') }}"
        };
    </script>

    <script type="module" src="{{ asset('js/stages.js') }}"></script>
@endsection
