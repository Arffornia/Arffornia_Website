@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/stages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/skeleton.css') }}">
@endsection

@section('content')
    <div class="bg">
        {{-- Milestone Info Panel --}}
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
                </div>
                <div id="newItemsContainer">
                    <p class="textTitle">New items:</p>
                    <ul></ul>
                </div>
                <div id="requiredItemsContainer">
                    <p class="textTitle">Required items:</p>
                    <ul></ul>
                </div>
                @if ($isAdmin ?? false)
                    <div class="admin-actions">
                        <button id="editBtn">Edit Milestone</button>
                        <button id="saveBtn" style="display: none;">Save</button>
                        <button id="cancelBtn" style="display: none;">Cancel</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Admin Control Panel --}}
        @if ($isAdmin ?? false)
            <div class="admin-panel">
                <button id="exportStagesBtn" class="export-btn">Export Data</button>
                <div class="stage-controls">
                    <p>Stages:</p>
                    <button id="addStageBtn" class="admin-mode-btn">Add Stage</button>
                    <button id="deleteStageBtn" class="admin-mode-btn">Delete Stage by Number</button>
                </div>
                <div class="admin-controls">
                    <p>Milestones:</p>
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

    {{-- Milestone Items Modal --}}
    <div id="item-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="modal-title">Edit Item</h3>
            <form id="item-editor-form">
                <input type="hidden" id="modal-item-id">
                <input type="hidden" id="modal-item-type">

                <label for="modal-item-id-input">Item ID (e.g., minecraft:iron_ingot)</label>
                <input type="text" id="modal-item-id-input" required>

                <label for="modal-display-name">Display Name</label>
                <input type="text" id="modal-display-name">

                <label for="modal-image-path">Image Path (in public/images/item_textures/)</label>
                <input type="text" id="modal-image-path">

                <div id="unlock-fields">
                    <label for="modal-recipe-id">Recipe to Ban</label>
                    <input type="text" id="modal-recipe-id">
                    <label for="modal-shop-price">Shop Price</label>
                    <input type="number" id="modal-shop-price">
                </div>

                <div id="requirement-fields">
                    <label for="modal-amount">Amount</label>
                    <input type="number" id="modal-amount" min="1">
                </div>

                <div class="modal-actions">
                    <button type="submit">Save</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stage Creation Modal --}}
    <div id="stage-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="stage-modal-title">Create Stage</h3>
            <form id="stage-editor-form">
                <input type="hidden" id="stage-modal-id">

                <label for="stage-modal-name">Stage Name</label>
                <input type="text" id="stage-modal-name" required>

                <label for="stage-modal-description">Description</label>
                <textarea id="stage-modal-description" rows="4"></textarea>

                <label for="stage-modal-points">Reward Points</label>
                <input type="number" id="stage-modal-points" min="0" required>

                <div class="modal-actions">
                    <button type="submit">Save Stage</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
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
