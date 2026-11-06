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
                    <p class="description-row">
                        <span class="textTitle"></span>
                        <span id="description" class="textDescription"></span>
                    </p>

                    <p><span class="textTitle">{{ __('stages.id') }} </span><span id="milestoneIdDisplay" class="textDescription"></span></p>
                    <p><span class="textTitle">{{ __('stages.stage') }} </span><span id="stageNumber" class="textDescription"></span></p>
                    <p><span class="textTitle">{{ __('stages.points') }} </span><span id="reward_progress_points"
                            class="textDescription"></span></p>
                </div>

                <div id="newItemsContainer">
                    <p class="textTitle">{{ __('stages.new_items') }}</p>
                    <ul></ul>
                </div>
                <div id="requiredItemsContainer">
                    <p class="textTitle">{{ __('stages.required_items') }}</p>
                    <ul></ul>
                </div>
                @if ($isAdmin ?? false)
                    <div class="admin-actions">
                        <button id="editBtn">{{ __('stages.admin.edit_milestone') }}</button>
                        <button id="saveBtn" style="display: none;">{{ __('common.save') }}</button>
                        <button id="cancelBtn" style="display: none;">{{ __('common.cancel') }}</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Admin Control Panel --}}
        @if ($isAdmin ?? false)
            <div class="admin-panel">
                <div class="graph-controls">
                    <p>{{ __('stages.admin.graphs') }}</p>
                    <button id="addGraphBtn" class="admin-mode-btn">{{ __('stages.admin.add_graph') }}</button>
                    <button id="editGraphBtn" class="admin-mode-btn">{{ __('stages.admin.edit_graph') }}</button>
                    <button id="deleteGraphBtn" class="admin-mode-btn">{{ __('stages.admin.delete_graph') }}</button>
                </div>

                <div class="stage-controls">
                    <p>{{ __('stages.admin.stages') }}</p>
                    <button id="addStageBtn" class="admin-mode-btn">{{ __('stages.admin.add_stage') }}</button>
                    <button id="deleteStageBtn" class="admin-mode-btn">{{ __('stages.admin.delete_stage') }}</button>
                </div>
                <div class="admin-controls">
                    <p>{{ __('stages.admin.milestones') }}</p>
                    <button class="admin-mode-btn active" data-mode="view">{{ __('stages.admin.mode_view') }}</button>
                    <button class="admin-mode-btn" data-mode="add">{{ __('stages.admin.mode_add') }}</button>
                    <button class="admin-mode-btn" data-mode="delete">{{ __('stages.admin.mode_delete') }}</button>
                    <button class="admin-mode-btn" data-mode="link">{{ __('stages.admin.mode_link') }}</button>
                    <button class="admin-mode-btn" data-mode="unlink">{{ __('stages.admin.mode_unlink') }}</button>
                    <button class="admin-mode-btn" data-mode="move">{{ __('stages.admin.mode_move') }}</button>
                </div>
            </div>

            <div class="data-io-panel">
                <button id="exportStagesBtn" class="data-io-btn">{{ __('stages.admin.export') }}</button>
                <button id="importStagesBtn" class="data-io-btn">{{ __('stages.admin.import') }}</button>
                <input type="file" id="importFileInput" style="display: none;" accept=".json">
            </div>
        @endif

        <button id="resetViewBtn" class="reset-view-btn" title="{{ __('stages.reset_view') }}">
            <img src="{{ asset('images/home_icon.png') }}" alt="{{ __('common.home') }}" />
        </button>

        <div class="sidebar-tabs">
            <div class="sidebar-main">
                <div id="category-container" class="sidebar-content"></div>
                <div class="sidebar-search-container">
                    <input type="text" id="graph-search" placeholder="{{ __('stages.search_placeholder') }}"
                        autocomplete="off">
                </div>
            </div>
        </div>

        <div class="canvas"></div>
    </div>

    {{-- Graph Editor Modal --}}
    <div id="graph-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="graph-modal-title">{{ __('stages.admin.graph_modal_title') }}</h3>
            <form id="graph-editor-form">
                <input type="hidden" id="graph-modal-id">

                <label for="graph-modal-name">{{ __('stages.admin.graph_name') }}</label>
                <input type="text" id="graph-modal-name" required>

                <label for="graph-modal-icon">{{ __('stages.admin.graph_icon') }}</label>
                <input type="text" id="graph-modal-icon" required>

                <label for="graph-modal-categories">{{ __('stages.admin.graph_categories') }}</label>
                <input type="text" id="graph-modal-categories" placeholder="{{ __('stages.admin.graph_categories_placeholder') }}">

                <div class="modal-actions">
                    <button type="submit">{{ __('stages.admin.save_graph') }}</button>
                    <button type="button" class="cancel-btn">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Milestone Items Modal --}}
    <div id="item-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="modal-title">{{ __('stages.admin.item_modal_title') }}</h3>
            <form id="item-editor-form">
                <input type="hidden" id="modal-item-id">
                <input type="hidden" id="modal-item-type">

                <label for="modal-item-id-input">{{ __('stages.admin.item_id') }}</label>
                <input type="text" id="modal-item-id-input" required>

                <label for="modal-display-name">{{ __('stages.admin.display_name') }}</label>
                <input type="text" id="modal-display-name">

                <label for="modal-image-path">{{ __('stages.admin.image_path') }}</label>
                <input type="text" id="modal-image-path">


                <div id="unlock-fields">
                    <label>{{ __('stages.admin.recipes_to_ban') }}</label>
                    <div id="banned-recipes-container"></div>
                    <button type="button" id="add-banned-recipe-btn" class="add-btn" style="margin-top: 10px;">{{ __('stages.admin.add_recipe') }}</button>

                    <label for="modal-shop-price">{{ __('stages.admin.shop_price') }}</label>
                    <input type="number" id="modal-shop-price">
                </div>



                <div id="requirement-fields">
                    <label for="modal-amount">{{ __('stages.admin.amount') }}</label>
                    <input type="number" id="modal-amount" min="1">
                </div>

                <div class="modal-actions">
                    <button type="submit">{{ __('common.save') }}</button>
                    <button type="button" class="cancel-btn">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stage Creation Modal --}}
    <div id="stage-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="stage-modal-title">{{ __('stages.admin.stage_modal_title') }}</h3>
            <form id="stage-editor-form">
                <input type="hidden" id="stage-modal-id">

                <label for="stage-modal-name">{{ __('stages.admin.stage_name') }}</label>
                <input type="text" id="stage-modal-name" required>

                <label for="stage-modal-description">{{ __('stages.admin.description') }}</label>
                <textarea id="stage-modal-description" rows="4"></textarea>

                <label for="stage-modal-points">{{ __('stages.admin.reward_points') }}</label>
                <input type="number" id="stage-modal-points" min="0" required>

                <div class="modal-actions">
                    <button type="submit">{{ __('stages.admin.save_stage') }}</button>
                    <button type="button" class="cancel-btn">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Recipe Editor Modal --}}
    <div id="recipe-editor-modal" class="editor-modal modal-hidden">
        <div class="modal-content">
            <span class="modal-close-btn">×</span>
            <h3 id="recipe-modal-title">{{ __('stages.admin.recipe_modal_title') }}</h3>
            <form id="recipe-editor-form">
                <input type="hidden" id="recipe-modal-unlock-id">

                <h4>{{ __('stages.admin.ingredients') }}</h4>
                <div id="recipe-ingredients-container">
                </div>
                <button type="button" id="add-ingredient-btn" class="add-btn">{{ __('stages.admin.add_ingredient') }}</button>

                <hr style="margin: 20px 0;">

                <h4>{{ __('stages.admin.results') }}</h4>
                <div id="recipe-results-container">
                </div>
                <button type="button" id="add-result-btn" class="add-btn">{{ __('stages.admin.add_result') }}</button>

                <hr style="margin: 20px 0;">

                <label for="recipe-energy">{{ __('stages.admin.energy') }}</label>
                <input type="number" id="recipe-energy" min="0">

                <label for="recipe-time">{{ __('stages.admin.time') }}</label>
                <input type="number" id="recipe-time" min="0">

                <div class="modal-actions">
                    <button type="submit">{{ __('stages.admin.save_recipe') }}</button>
                    <button type="button" class="cancel-btn">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.AppData = {
            graphs: @json($graphs),
            stages: @json($stages),
            milestones: @json($milestones),
            milestone_closure: @json($milestone_closure),
            isAdmin: @json($isAdmin ?? false),
            csrfToken: "{{ csrf_token() }}",
            baseUrl: "{{ url('/') }}",
            i18n: @json(__('stages.js'))
        };
    </script>
    <script type="module" src="{{ asset('js/stages.js') }}"></script>
@endsection
