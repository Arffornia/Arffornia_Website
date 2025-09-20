import { getIconSvgByType } from "./mod_icons.js";

const canvas = document.querySelector(".canvas");
const milestoneInfo = document.querySelector(".info");
const infoCloseBtnDiv = milestoneInfo.querySelector(".closeBtn");
const itemModal = document.getElementById('item-editor-modal');
const itemForm = document.getElementById('item-editor-form');
const stageModal = document.getElementById('stage-editor-modal');
const stageForm = document.getElementById('stage-editor-form');
const recipeModal = document.getElementById('recipe-editor-modal');
const recipeForm = document.getElementById('recipe-editor-form');
const bannedRecipesContainer = document.getElementById('banned-recipes-container');
const addBannedRecipeBtn = document.getElementById('add-banned-recipe-btn');

// Destructure AppData passed from Blade view
const { milestones, milestone_closure, isAdmin, csrfToken, baseUrl } = window.AppData;

// Constants for grid calculations
const GRID_CELL_SPACING = 80;
const NODE_CONTENT_DIAMETER = 70;
const NODE_BORDER_WIDTH = 5;
const NODE_ACTUAL_DIAMETER = NODE_CONTENT_DIAMETER + (2 * NODE_BORDER_WIDTH);

// --- START: Global state variables ---
/**
 * The current interaction mode for admins.
 * Can be 'view', 'add', 'delete', 'link', 'unlink'.
 * @type {string}
 */
let currentMode = 'view';

/**
 * Stores the ID of the first node selected during a link/unlink operation.
 * @type {string|null}
 */
let selectedSourceNode = null;

/**
 * Stores the ID of the currently displayed milestone in the info panel.
 * @type {string|null}
 */
var currentNodeId = null;

/**
 * Stores the data of the milestone currently displayed in the info panel. Used for editing.
 * @type {object|null}
 */
window.currentMilestoneData = null;

/**
 * Flag to indicate if the info panel is in edit mode.
 * @type {boolean}
 */
let isEditing = false;

/**
 * Stores the Sanctum API token to avoid repeated requests.
 * @type {string|null}
 */
let apiToken = null;

/**
 * Editor move vars
 */
let isDraggingNode = false;
let draggedNode = null;
let dragOffsetX = 0;
let dragOffsetY = 0;
// --- END: Global state variables ---


/**
 * Sets up all event listeners for the page.
 */
function setupEventListeners() {
    // Canvas panning logic
    let dragging = false;
    let offsetX, offsetY;
    canvas.addEventListener("mousedown", (e) => {
        // Only pan if clicking the background, not a node or a link
        if (e.target.closest('.node') || e.target.closest('.link-path')) return;
        dragging = true;
        offsetX = e.clientX - parseInt(window.getComputedStyle(canvas).left);
        offsetY = e.clientY - parseInt(window.getComputedStyle(canvas).top);
    });
    document.addEventListener("mousemove", (e) => {
        if (dragging) {
            let newLeft = e.clientX - offsetX;
            let newTop = e.clientY - offsetY;
            const bgRect = document.querySelector(".bg").getBoundingClientRect();
            const visibleWidth = bgRect.width;
            const visibleHeight = bgRect.height;
            const totalWidth = canvas.offsetWidth;
            const totalHeight = canvas.offsetHeight;
            newLeft = Math.min(0, Math.max(visibleWidth - totalWidth, newLeft));
            newTop = Math.min(0, Math.max(visibleHeight - totalHeight, newTop));
            canvas.style.left = `${newLeft}px`;
            canvas.style.top = `${newTop}px`;
        }
    });
    document.addEventListener("mouseup", () => {
        dragging = false;
    });

    // Move mode event listerners
    document.addEventListener('mousedown', startDragNode);
    document.addEventListener('mousemove', dragNode);
    document.addEventListener('mouseup', endDragNode);


    // Main click handler for the canvas
    canvas.addEventListener('click', handleCanvasClick);

    // Info panel close button
    infoCloseBtnDiv.addEventListener('click', hideMilestoneInfo);

    window.addEventListener("resize", resizeCanvasToFitViewport);

    // Setup admin-specific listeners
    if (isAdmin) {
        setupAdminEventListeners();
    }

    itemModal.querySelector('.modal-close-btn').addEventListener('click', closeItemModal);
    itemModal.querySelector('.cancel-btn').addEventListener('click', closeItemModal);

    stageModal.querySelector('.modal-close-btn').addEventListener('click', closeStageModal);
    stageModal.querySelector('.cancel-btn').addEventListener('click', closeStageModal);
    stageForm.addEventListener('submit', handleStageFormSubmit);

    recipeModal.querySelector('.modal-close-btn').addEventListener('click', closeRecipeModal);
    recipeModal.querySelector('.cancel-btn').addEventListener('click', closeRecipeModal);
    document.getElementById('add-ingredient-btn').addEventListener('click', addIngredientField);
    document.getElementById('add-result-btn').addEventListener('click', addResultField);
    recipeForm.addEventListener('submit', handleRecipeFormSubmit);

    addBannedRecipeBtn.addEventListener('click', () => createBannedRecipeInput());

    itemForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const type = document.getElementById('modal-item-type').value;
        const itemId = document.getElementById('modal-item-id').value;
        const milestoneId = milestoneInfo.dataset.milestoneId;

        let url, method;
        let body = {
            item_id: document.getElementById('modal-item-id-input').value,
            display_name: document.getElementById('modal-display-name').value,
            image_path: document.getElementById('modal-image-path').value,
        };

        if (type === 'unlocks') {
            const recipesToBan = Array.from(document.querySelectorAll('.banned-recipe-value'))
                .map(input => input.value.trim())
                .filter(value => value !== '');
            body.recipes_to_ban = recipesToBan;

            body.shop_price = parseInt(document.getElementById('modal-shop-price').value) || null;
            url = itemId ? `${baseUrl}/api/unlocks/${itemId}` : `${baseUrl}/api/milestones/${milestoneId}/unlocks`;
        } else {
            body.amount = parseInt(document.getElementById('modal-amount').value);
            url = itemId ? `${baseUrl}/api/requirements/${itemId}` : `${baseUrl}/api/milestones/${milestoneId}/requirements`;
        }

        method = itemId ? 'PUT' : 'POST';

        try {
            const token = await getApiToken();
            const response = await fetch(url, { method, headers: createApiHeaders(token), body: JSON.stringify(body) });
            if (!response.ok) throw new Error(`API error: ${response.statusText}`);

            showMilestonesInfo(milestones.find(m => m.id == milestoneId));
            closeItemModal();

        } catch (error) {
            console.error('Failed to save item:', error);
            alert('Error: ' + error.message);
        }
    });

    milestoneInfo.addEventListener('click', async (e) => {
        if (e.target.matches('.item-action-btn.edit')) {
            const itemId = parseInt(e.target.dataset.itemId, 10);
            const itemType = e.target.dataset.itemType;
            openItemModal(itemType, itemId);
        }
        if (e.target.matches('.item-action-btn.delete')) {
            if (!confirm('Are you sure you want to delete this item?')) return;

            const itemId = e.target.dataset.itemId;
            const itemType = e.target.dataset.itemType;
            const url = `${baseUrl}/api/${itemType}/${itemId}`;

            try {
                const token = await getApiToken();
                const response = await fetch(url, { method: 'DELETE', headers: createApiHeaders(token) });
                if (!response.ok) throw new Error('Failed to delete item');

                showMilestonesInfo(milestones.find(m => m.id == milestoneInfo.dataset.milestoneId));
            } catch (error) {
                console.error(error);
                alert('Error deleting item.');
            }
        }

        if (e.target.closest('.recipe-edit-btn')) {
            const unlockId = e.target.closest('.recipe-edit-btn').dataset.unlockId;
            openRecipeModal(unlockId);
        }
    });
}

/**
 * Sets up event listeners for admin controls.
 */
function setupAdminEventListeners() {
    const exportBtn = document.getElementById('exportStagesBtn');
    const importBtn = document.getElementById('importStagesBtn');
    const importFileInput = document.getElementById('importFileInput');

    if (exportBtn) {
        exportBtn.addEventListener('click', handleExport);
    }

    if (importBtn && importFileInput) {
        importBtn.addEventListener('click', () => importFileInput.click());
        importFileInput.addEventListener('change', handleFileImport);
    }

    document.getElementById('addStageBtn').addEventListener('click', handleAddStage);
    document.getElementById('deleteStageBtn').addEventListener('click', handleDeleteStage);

    // Edit/Save/Cancel buttons in the info panel
    document.getElementById('editBtn').addEventListener('click', () => setEditMode(true));
    document.getElementById('cancelBtn').addEventListener('click', () => setEditMode(false));
    document.getElementById('saveBtn').addEventListener('click', handleSave);

    // Mode-switching buttons
    const adminModeButtons = document.querySelectorAll('.admin-controls .admin-mode-btn');
    adminModeButtons.forEach(button => {
        button.addEventListener('click', () => {
            adminModeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentMode = button.dataset.mode;
            resetSelection();
            updateCanvasCursor();
        });
    });
}

/**
 * Main click handler, delegates actions based on the current mode.
 * @param {MouseEvent} event
 */
function handleCanvasClick(event) {
    const clickedNodeEl = event.target.closest('.node');
    const clickedPathEl = event.target.closest('.link-path');

    if (isAdmin) { // Admin interactions
        // START: Prioritize link clicks in unlink mode
        if (currentMode === 'unlink' && clickedPathEl) {
            handleDeleteLinkByClick(clickedPathEl);
            return; // Stop processing to prevent other actions
        }
        // END: Prioritize link clicks

        switch (currentMode) {
            case 'add':
                if (!clickedNodeEl) handleAddNode(event);
                return;
            case 'delete':
                if (clickedNodeEl) handleDeleteNode(clickedNodeEl.id);
                return;
            case 'link': // Unlink is now handled by direct click, so this only does linking
                if (clickedNodeEl) handleLinkNode(clickedNodeEl);
                return;
        }
    }

    // Default 'view' mode interaction for everyone
    if (clickedNodeEl) {
        if (clickedNodeEl.id !== currentNodeId) {
            showMilestonesInfo(milestones.find(m => m.id == clickedNodeEl.id));
            currentNodeId = clickedNodeEl.id;
        }
    } else if (!clickedPathEl) { // Hide info panel only if clicking the background
        hideMilestoneInfo();
        resetSelection();
        currentNodeId = null;
    }
}


// --- START: Admin Action Functions ---

/**
 * Handles deleting a link by directly clicking on its SVG path.
 * @param {SVGPathElement} pathElement The clicked SVG path element.
 */
async function handleDeleteLinkByClick(pathElement) {
    const sourceId = pathElement.dataset.sourceId;
    const targetId = pathElement.dataset.targetId;

    if (!sourceId || !targetId) {
        console.error("Link is missing source/target ID attributes.", pathElement);
        return;
    }

    if (!confirm(`Are you sure you want to delete the link from node ${sourceId} to ${targetId}?`)) {
        return;
    }

    const token = await getApiToken();
    if (!token) return;

    try {
        const response = await fetch(`${baseUrl}/api/milestone-closures`, {
            method: 'DELETE',
            headers: createApiHeaders(token),
            body: JSON.stringify({ source_id: sourceId, target_id: targetId })
        });

        if (!response.ok) {
            throw new Error(`Failed to delete link. Server status: ${response.status}`);
        }

        // On success, update UI and local data
        pathElement.parentElement.remove(); // Remove the entire <svg> container
        const linkIndex = milestone_closure.findIndex(c => c.milestone_id == sourceId && c.descendant_id == targetId);
        if (linkIndex > -1) {
            milestone_closure.splice(linkIndex, 1);
        }

        console.log('Link deleted successfully!');

    } catch (error) {
        console.error('Delete Link Error:', error);
        alert(`Error: ${error.message}`);
    }
}

/**
 * Handles the process of adding a new node on the canvas.
 * @param {MouseEvent} event
 */
async function handleAddNode(event) {
    const name = prompt("Enter new node name:", "New Milestone");
    if (!name) return;
    const stageId = prompt("Enter Stage ID:", "1");
    if (!stageId || isNaN(parseInt(stageId))) {
        alert("Invalid Stage ID.");
        return;
    }

    const canvasRect = canvas.getBoundingClientRect();
    const clickX = event.clientX - canvasRect.left;
    const clickY = event.clientY - canvasRect.top;

    const gridX = Math.round(clickX / GRID_CELL_SPACING);
    const gridY = Math.round(clickY / GRID_CELL_SPACING);

    const newNodeData = {
        name,
        description: "A new adventure begins...",
        stage_id: parseInt(stageId),
        icon_type: 'default',
        x: gridX,
        y: gridY,
        reward_progress_points: 10,
    };

    const token = await getApiToken();
    if (!token) return;

    try {
        const response = await fetch(`${baseUrl}/api/milestones`, {
            method: 'POST',
            headers: createApiHeaders(token),
            body: JSON.stringify(newNodeData)
        });

        if (!response.ok) {
            if (response.status === 422) {
                const errorData = await response.json();

                if (errorData.errors && errorData.errors.stage_id) {
                    throw new Error(errorData.errors.stage_id[ 0 ]);
                }
            }
            throw new Error(`Server responded with ${response.status}`);
        }

        const createdNode = await response.json();

        milestones.push(createdNode);
        canvas.appendChild(createNode(createdNode));
        console.log('Node created successfully!');
    } catch (error) {
        console.error('Add Node Error:', error);
        alert(`Error creating node: ${error.message}`);
    }
}


/**
 * Handles deleting a node after confirmation.
 * @param {string} nodeId
 */
async function handleDeleteNode(nodeId) {
    if (!confirm(`Are you sure you want to delete node ${nodeId}? This will also remove all its links. This cannot be undone.`)) return;

    const token = await getApiToken();
    if (!token) return;

    try {
        const response = await fetch(`${baseUrl}/api/milestones/${nodeId}`, {
            method: 'DELETE',
            headers: createApiHeaders(token)
        });
        if (!response.ok) throw new Error(`Server responded with ${response.status}`);

        document.getElementById(nodeId)?.remove();
        const index = milestones.findIndex(m => m.id == nodeId);
        if (index > -1) milestones.splice(index, 1);

        window.AppData.milestone_closure = milestone_closure.filter(link => link.milestone_id != nodeId && link.descendant_id != nodeId);
        redrawAllLinks();
        console.log('Node deleted successfully!');
    } catch (error) {
        console.error('Delete Node Error:', error);
        alert(`Error deleting node: ${error.message}`);
    }
}

/**
 * Handles the two-click process for creating a link between nodes.
 * @param {HTMLElement} targetNodeEl
 */
async function handleLinkNode(targetNodeEl) {
    const targetId = targetNodeEl.id;

    if (!selectedSourceNode) {
        selectedSourceNode = targetId;
        targetNodeEl.classList.add('selected');
    } else {
        if (selectedSourceNode === targetId) {
            resetSelection();
            return;
        }

        const sourceId = selectedSourceNode;
        const token = await getApiToken();
        if (!token) { resetSelection(); return; }

        try {
            const response = await fetch(`${baseUrl}/api/milestone-closures`, {
                method: 'POST',
                headers: createApiHeaders(token),
                body: JSON.stringify({ source_id: sourceId, target_id: targetId })
            });
            if (!response.ok) {
                if (response.status === 409) throw new Error('Link already exists!');
                throw new Error(`Failed to link nodes. Status: ${response.status}`);
            }

            const newLink = { milestone_id: Number(sourceId), descendant_id: Number(targetId) };
            milestone_closure.push(newLink);
            linkNodes(sourceId, targetId);
            console.log(`Linked ${sourceId} to ${targetId}`);

        } catch (error) {
            console.error(`Link Error:`, error);
            alert(`Error: ${error.message}`);
        } finally {
            resetSelection();
        }
    }
}

/**
 * Handles adding a new stage by opening the modal.
 */
function handleAddStage() {
    openStageModal();
}

/**
 * Handles deleting a stage by prompting for its NUMBER.
 */
async function handleDeleteStage() {
    const stageNumber = prompt("Enter the NUMBER of the stage to delete:");
    if (!stageNumber || isNaN(parseInt(stageNumber))) {
        alert("Invalid stage number provided.");
        return;
    }

    // Find the stage in our AppData to get its ID for the API endpoint
    const stageToDelete = window.AppData.stages.find(s => s.number == stageNumber);

    if (!stageToDelete) {
        alert(`Stage with number ${stageNumber} not found.`);
        return;
    }

    if (!confirm(`Are you sure you want to delete Stage ${stageNumber} (ID: ${stageToDelete.id})? This will only work if no milestones are assigned to it.`)) {
        return;
    }

    const token = await getApiToken();
    if (!token) return;

    try {
        const response = await fetch(`${baseUrl}/api/stages/${stageToDelete.id}`, {
            method: 'DELETE',
            headers: createApiHeaders(token)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || `Server responded with ${response.status}`);
        }

        const index = window.AppData.stages.findIndex(s => s.id == stageToDelete.id);
        if (index > -1) {
            window.AppData.stages.splice(index, 1);
        }
        alert(`Stage ${stageNumber} deleted successfully.`);

    } catch (error) {
        console.error('Delete Stage Error:', error);
        alert(`Error deleting stage: ${error.message}`);
    }
}


/**
 * Opens the stage editor modal for creation.
 */
function openStageModal() {
    stageModal.classList.remove('modal-hidden');
    stageForm.reset();

    const nextNumber = (Math.max(...window.AppData.stages.map(s => s.number), 0)) + 1;
    document.getElementById('stage-modal-title').textContent = `Create Stage #${nextNumber}`;
    document.getElementById('stage-modal-id').value = ''; // Clear ID for creation
}

/**
 * Closes the stage editor modal.
 */
function closeStageModal() {
    stageModal.classList.add('modal-hidden');
}

/**
 * Handles the submission of the stage creation/edit form.
 * @param {Event} e
 */
async function handleStageFormSubmit(e) {
    e.preventDefault();
    const token = await getApiToken();
    if (!token) return;

    const stageData = {
        name: document.getElementById('stage-modal-name').value,
        description: document.getElementById('stage-modal-description').value,
        reward_progress_points: parseInt(document.getElementById('stage-modal-points').value)
    };

    const url = `${baseUrl}/api/stages`;
    const method = 'POST';

    try {
        const response = await fetch(url, { method, headers: createApiHeaders(token), body: JSON.stringify(stageData) });
        if (!response.ok) {
            if (response.status === 422) {
                const errorData = await response.json();
                const firstErrorKey = Object.keys(errorData.errors)[ 0 ];
                throw new Error(errorData.errors[ firstErrorKey ][ 0 ]);
            }
            throw new Error(`Server error: ${response.statusText}`);
        }
        const newStage = await response.json();
        window.AppData.stages.push(newStage);
        alert(`Stage "${newStage.name}" (Number: ${newStage.number}) created successfully!`);
        closeStageModal();

    } catch (error) {
        console.error('Failed to save stage:', error);
        alert('Error: ' + error.message);
    }
}


/**
 * Resets the source node selection and its visual indicator.
 */
function resetSelection() {
    if (selectedSourceNode) {
        document.getElementById(selectedSourceNode)?.classList.remove('selected');
    }
    selectedSourceNode = null;
}

/**
 * Updates the canvas cursor style based on the current mode.
 */
function updateCanvasCursor() {
    canvas.classList.remove('add-mode', 'delete-mode', 'link-mode', 'unlink-mode', 'move-mode');
    if (isAdmin && currentMode !== 'view') {
        canvas.classList.add(`${currentMode}-mode`);
    }
}

/**
 * Hides the milestone info panel.
 */
function hideMilestoneInfo() {
    milestoneInfo.classList.remove("info-show");
    currentNodeId = null;
}

/**
 * Fetches and displays information for a given milestone.
 * @param {object} milestone The milestone object from the initial data.
 */
function showMilestonesInfo(milestone) {
    if (!milestone) return;

    milestoneInfo.classList.add("info-show");
    const loader = document.getElementById("info-loader");
    const content = document.getElementById("info-content");

    loader.style.display = "block";
    content.style.display = "none";
    setEditMode(false);

    fetch(`${baseUrl}/api/milestone/get/${milestone.id}`)
        .then(response => response.ok ? response.json() : Promise.reject('API Error'))
        .then(data => {
            return new Promise(resolve => {
                setTimeout(() => resolve(data), 1000 * 0.3);
            });
        })
        .then(data => {
            window.currentMilestoneData = data;
            milestoneInfo.querySelector('#milestone-title').textContent = data.name;
            milestoneInfo.querySelector("#description").textContent = data.description;
            milestoneInfo.querySelector("#stageNumber").textContent = data.stage_id;
            milestoneInfo.querySelector("#reward_progress_points").textContent = data.reward_progress_points;

            const iconContentElement = milestoneInfo.querySelector("#iconContent");
            iconContentElement.innerHTML = getIconSvgByType(data.icon_type);
            iconContentElement.title = `Milestone ID: ${data.id}`;

            const newItemsContainer = milestoneInfo.querySelector("#newItemsContainer");
            const requiredItemsContainer = milestoneInfo.querySelector("#requiredItemsContainer");

            milestoneInfo.dataset.milestoneId = data.id;

            updateItemsList(data.unlocks, 'unlocks', newItemsContainer);
            updateItemsList(data.requirements, 'requirements', requiredItemsContainer);
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            milestoneInfo.querySelector('#milestone-title').textContent = "Error loading data";
        })
        .finally(() => {
            loader.style.display = "none";
            content.style.display = "block";
            if (isAdmin) {
                document.querySelector('.admin-actions').style.display = 'flex';
            }
        });
}

/**
 * Draws a line or curve between two nodes.
 * @param {string|number} nodeId1
 * @param {string|number} nodeId2
 */
function linkNodes(nodeId1, nodeId2) {
    const node1 = document.getElementById(nodeId1);
    const node2 = document.getElementById(nodeId2);
    if (!node1 || !node2) return;

    function getCenterRelativeToCanvas(div) {
        const centerX = parseInt(div.style.left) + div.offsetWidth / 2;
        const centerY = parseInt(div.style.top) + div.offsetHeight / 2;
        return { x: centerX, y: centerY };
    }

    const center1 = getCenterRelativeToCanvas(node1);
    const center2 = getCenterRelativeToCanvas(node2);

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "100%");
    svg.setAttribute("height", "100%");
    svg.setAttribute("style", "position: absolute; top: 0; left: 0; z-index: -1; pointer-events: none;");

    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");

    // START: Add class and data attributes for interactivity
    path.classList.add("link-path"); // General class for all links
    path.setAttribute("data-source-id", nodeId1);
    path.setAttribute("data-target-id", nodeId2);
    path.style.pointerEvents = 'stroke'; // Makes the line clickable but not the empty space around it
    // END: Add class and data attributes

    const deltaY = center2.y - center1.y;
    const curveStrength = Math.min(Math.abs(deltaY) * 0.6, 150);
    const d = `M ${center1.x},${center1.y} C ${center1.x},${center1.y + curveStrength} ${center2.x},${center2.y - curveStrength} ${center2.x},${center2.y}`;
    path.setAttribute("d", d);
    path.setAttribute("fill", "none");
    path.classList.add("line"); // Keep original line class for styling

    svg.appendChild(path);
    canvas.appendChild(svg);
}


/**
 * Creates and returns a node element.
 * @param {object} milestone
 * @returns {HTMLElement}
 */
function createNode(milestone) {
    const node = document.createElement("div");
    node.className = "node";
    node.id = milestone.id;
    node.title = milestone.name;
    node.style.position = 'absolute';
    const topLeftX = (milestone.x * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);
    const topLeftY = (milestone.y * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);
    node.style.left = `${topLeftX}px`;
    node.style.top = `${topLeftY}px`;

    const icon = document.createElement("div");
    icon.className = "icon";
    icon.innerHTML = getIconSvgByType(milestone.icon_type);

    node.appendChild(icon);
    return node;
}

/**
 * Creates and appends a new input field for a banned recipe.
 * @param {string} value The initial value for the input field.
 */
function createBannedRecipeInput(value = '') {
    const div = document.createElement('div');
    div.className = 'banned-recipe-field';
    div.style.display = 'flex';
    div.style.gap = '10px';
    div.style.marginBottom = '5px';

    div.innerHTML = `
        <input type="text" class="banned-recipe-value" placeholder="e.g., minecraft:stick" value="${value}" required style="flex-grow: 1;">
        <button type="button" class="remove-btn">Remove</button>
    `;

    bannedRecipesContainer.appendChild(div);

    div.querySelector('.remove-btn').addEventListener('click', () => {
        div.remove();
    });
}

/**
 * Initial function to build the entire tree on page load.
 */
function buildTrees() {
    canvas.innerHTML = ''; // Clear canvas before building
    milestones.forEach(milestone => {
        canvas.appendChild(createNode(milestone));
    });
    redrawAllLinks();
    resizeCanvasToFitViewport();
}

/**
 * Removes all SVG lines and redraws them based on current data.
 */
function redrawAllLinks() {
    document.querySelectorAll('.canvas > svg').forEach(svg => svg.remove());
    milestone_closure.forEach(closure => {
        if (document.getElementById(closure.milestone_id) && document.getElementById(closure.descendant_id)) {
            linkNodes(closure.milestone_id, closure.descendant_id);
        }
    });
}

/**
 * Dynamically resizes the canvas to ensure all nodes are visible.
 */
function resizeCanvasToFitViewport() {
    let maxX = 0, maxY = 0;
    document.querySelectorAll(".node").forEach(node => {
        const x = parseInt(node.style.left || 0) + node.offsetWidth;
        const y = parseInt(node.style.top || 0) + node.offsetHeight;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    });

    const padding = 200;
    canvas.style.width = `${Math.max(window.innerWidth, maxX + padding)}px`;
    canvas.style.height = `${Math.max(window.innerHeight, maxY + padding)}px`;
}


// --- Admin Helper Functions ---

/**
 * Gets the Sanctum API token for authenticated requests. Caches the token.
 * @returns {Promise<string|null>}
 */
async function getApiToken() {
    if (apiToken) return apiToken;
    try {
        const response = await fetch(`${baseUrl}/api/auth/token/session`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        if (!response.ok) throw new Error('Token fetch failed');
        const data = await response.json();
        apiToken = data.token;
        return apiToken;
    } catch (error) {
        console.error("Token Error:", error);
        alert("Authentication Error. Please refresh the page and try again.");
        return null;
    }
}

/**
 * Creates a standard set of headers for API requests.
 * @param {string} token
 * @returns {object}
 */
function createApiHeaders(token) {
    return {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    };
}

/**
 * Handles exporting the stage data as a JSON file.
 */
async function handleExport() {
    const token = await getApiToken();
    if (!token) return;
    try {
        const res = await fetch(`${baseUrl}/api/stages/export`, {
            method: 'POST',
            headers: createApiHeaders(token)
        });
        const data = await res.json();
        const blob = new Blob([ JSON.stringify(data, null, 2) ], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `arffornia-stages-backup-${new Date().toISOString().slice(0, 10)}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (err) {
        console.error("Export failed", err);
        alert("Failed to export data.");
    }
}

async function handleFileImport(event) {
    const file = event.target.files[ 0 ];
    if (!file) {
        return;
    }

    if (!confirm("ATTENTION: This will replace ALL existing stages, milestones, and progression data. This action cannot be undone. Are you sure you want to continue?")) {
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = async (e) => {
        const fileContent = e.target.result;

        try {
            JSON.parse(fileContent);

            const token = await getApiToken();
            if (!token) {
                alert("Authentication error. Could not get API token.");
                return;
            }

            const response = await fetch(`${baseUrl}/api/stages/import`, {
                method: 'POST',
                headers: createApiHeaders(token),
                body: fileContent,
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Import failed.');
            }

            alert(result.message);
            window.location.reload();
        } catch (error) {
            console.error('Import Error:', error);
            alert(`Import failed: ${error.message}`);
        } finally {
            event.target.value = '';
        }
    };

    reader.readAsText(file);
}

/**
 * Toggles the info panel between view and edit modes.
 * @param {boolean} editing
 */
function setEditMode(editing) {
    if (!isAdmin) {
        return;
    }

    isEditing = editing;
    const titleContainer = milestoneInfo.querySelector('#milestone-title');
    const descriptionContainer = milestoneInfo.querySelector('#description');
    const pointsContainer = milestoneInfo.querySelector('#reward_progress_points');
    const stageContainer = milestoneInfo.querySelector('#stageNumber');

    document.getElementById('editBtn').style.display = editing ? 'none' : 'inline-block';
    document.getElementById('saveBtn').style.display = editing ? 'inline-block' : 'none';
    document.getElementById('cancelBtn').style.display = editing ? 'inline-block' : 'none';

    if (editing) {
        titleContainer.innerHTML = `<input type="text" class="title-input" value="${window.currentMilestoneData.name}">`;
        descriptionContainer.innerHTML = `<textarea class="description-input">${window.currentMilestoneData.description}</textarea>`;
        pointsContainer.innerHTML = `<input type="number" class="points-input" value="${window.currentMilestoneData.reward_progress_points}">`;
        stageContainer.innerHTML = `<input type="number" class="stage-input" value="${window.currentMilestoneData.stage_id}">`;
    } else if (window.currentMilestoneData) {
        titleContainer.textContent = window.currentMilestoneData.name;
        descriptionContainer.textContent = window.currentMilestoneData.description;
        pointsContainer.textContent = window.currentMilestoneData.reward_progress_points;
        stageContainer.textContent = window.currentMilestoneData.stage_id;
    }

    if (window.currentMilestoneData) {
        updateItemsList(window.currentMilestoneData.unlocks, 'unlocks', milestoneInfo.querySelector("#newItemsContainer"));
        updateItemsList(window.currentMilestoneData.requirements, 'requirements', milestoneInfo.querySelector("#requiredItemsContainer"));
    }
}

/**
 * Saves the edited milestone data via an API call.
 */
async function handleSave() {
    const token = await getApiToken();
    if (!token) return;

    const updatedData = {
        name: milestoneInfo.querySelector('.title-input').value,
        description: milestoneInfo.querySelector('.description-input').value,
        reward_progress_points: parseInt(milestoneInfo.querySelector('.points-input').value, 10),
        stage_id: parseInt(milestoneInfo.querySelector('.stage-input').value, 10),
    };

    try {
        const response = await fetch(`${baseUrl}/api/milestones/${window.currentMilestoneData.id}`, {
            method: 'PUT',
            headers: createApiHeaders(token),
            body: JSON.stringify(updatedData)
        });
        if (!response.ok) throw new Error('Save failed');
        const savedData = await response.json();

        window.currentMilestoneData = savedData;
        const milestoneInArray = milestones.find(m => m.id == savedData.id);
        if (milestoneInArray) {
            Object.assign(milestoneInArray, savedData);
            document.getElementById(savedData.id).title = savedData.name;
        }

        setEditMode(false);
        console.log("Milestone updated successfully!");
    } catch (error) {
        console.error('Save Error:', error);
        alert(`Failed to save changes: ${error.message}`);
    }
}

/**
 * Starts dragging a node if in 'move' mode.
 * @param {MouseEvent} e
 */
function startDragNode(e) {
    if (currentMode !== 'move' || !e.target.closest('.node')) return;

    isDraggingNode = true;
    draggedNode = e.target.closest('.node');

    const canvasRect = canvas.getBoundingClientRect();
    const nodeRect = draggedNode.getBoundingClientRect();

    dragOffsetX = e.clientX - nodeRect.left;
    dragOffsetY = e.clientY - nodeRect.top;

    draggedNode.classList.add('dragging');
    canvas.style.cursor = 'grabbing';
}

/**
 * Moves the node on the canvas.
 * @param {MouseEvent} e
 */
function dragNode(e) {
    if (!isDraggingNode || !draggedNode) return;

    e.preventDefault();
    const canvasRect = canvas.getBoundingClientRect();

    let newX = e.clientX - canvasRect.left - dragOffsetX;
    let newY = e.clientY - canvasRect.top - dragOffsetY;

    draggedNode.style.left = `${newX}px`;
    draggedNode.style.top = `${newY}px`;

    redrawAllLinks();
}

/**
 * Ends the drag operation, snaps to grid, and saves the new position.
 * @param {MouseEvent} e
 */
async function endDragNode(e) {
    if (!isDraggingNode || !draggedNode) return;

    const nodeId = draggedNode.id;
    draggedNode.classList.remove('dragging');
    canvas.style.cursor = '';

    const finalLeft = parseInt(draggedNode.style.left, 10);
    const finalTop = parseInt(draggedNode.style.top, 10);

    const gridX = Math.round((finalLeft + NODE_ACTUAL_DIAMETER / 2) / GRID_CELL_SPACING);
    const gridY = Math.round((finalTop + NODE_ACTUAL_DIAMETER / 2) / GRID_CELL_SPACING);

    const snappedLeft = (gridX * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);
    const snappedTop = (gridY * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);
    draggedNode.style.left = `${snappedLeft}px`;
    draggedNode.style.top = `${snappedTop}px`;

    redrawAllLinks();

    try {
        const token = await getApiToken();
        if (!token) throw new Error("API token not available.");

        await fetch(`${baseUrl}/api/milestones/${nodeId}/position`, {
            method: 'PUT',
            headers: createApiHeaders(token),
            body: JSON.stringify({ x: gridX, y: gridY })
        });

        const milestoneData = milestones.find(m => m.id == nodeId);
        if (milestoneData) {
            milestoneData.x = gridX;
            milestoneData.y = gridY;
        }
        console.log(`Node ${nodeId} moved to (${gridX}, ${gridY})`);

    } catch (error) {
        console.error('Failed to update node position:', error);
        alert('Error saving new position. Please check the console.');
    } finally {
        isDraggingNode = false;
        draggedNode = null;
    }
}

function updateItemsList(items, type, container) {
    if (!container) return;

    const list = container.querySelector('ul');
    if (!list) {
        console.error(`Could not find a 'ul' inside the provided container for type '${type}'.`, container);
        return;
    }

    list.innerHTML = '';
    const addBtnId = `add-${type}-btn`;

    if (isEditing) {
        container.insertAdjacentHTML('afterbegin', `<button class="item-action-btn" id="${addBtnId}">Add New</button>`);
        document.getElementById(addBtnId).addEventListener('click', () => openItemModal(type));
    } else {
        document.getElementById(addBtnId)?.remove();
    }

    if (items && items.length > 0) {
        items.forEach(item => {
            const li = document.createElement('li');
            li.dataset.itemId = item.id;
            let itemText = `
                <img src="${item.image_url}" alt="${item.display_name}" width="32" height="32" style="vertical-align: middle;">
                <span>${item.display_name}</span>`;
            if (type === 'requirements') {
                itemText += `<span> — x${item.amount}</span>`;
            } else if (type === 'unlocks') {
                itemText += `<span> (Prix: ${item.shop_price || 'N/A'})</span>`;
            }

            li.innerHTML = itemText;

            if (isEditing) {
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'item-actions-inline';
                let buttons = `
                    <button class="item-action-btn edit" data-item-id="${item.id}" data-item-type="${type}">Edit</button>
                    <button class="item-action-btn delete" data-item-id="${item.id}" data-item-type="${type}">Delete</button>
                `;

                if (type === 'unlocks') {
                    buttons += `<button class="recipe-edit-btn" data-unlock-id="${item.id}">Recipe</button>`;
                }

                actionsDiv.innerHTML = buttons;
                li.appendChild(actionsDiv);
            }
            list.appendChild(li);
        });
    } else {
        list.innerHTML = `<li>No ${type} defined for this milestone.</li>`;
    }
}


function openItemModal(type, itemId = null) {
    itemModal.classList.remove('modal-hidden');
    itemForm.reset();
    bannedRecipesContainer.innerHTML = '';

    document.getElementById('modal-item-type').value = type;
    document.getElementById('unlock-fields').style.display = type === 'unlocks' ? 'block' : 'none';
    document.getElementById('requirement-fields').style.display = type === 'requirements' ? 'block' : 'none';

    if (itemId) {
        document.getElementById('modal-title').textContent = `Edit ${type.slice(0, -1)}`;
        document.getElementById('modal-item-id').value = itemId;

        const itemData = window.currentMilestoneData[ type ].find(i => i.id === itemId);
        if (!itemData) return;

        document.getElementById('modal-item-id-input').value = itemData.item_id;
        document.getElementById('modal-display-name').value = itemData.display_name;
        document.getElementById('modal-image-path').value = itemData.image_path;

        if (type === 'unlocks') {
            if (Array.isArray(itemData.recipes_to_ban)) {
                itemData.recipes_to_ban.forEach(recipeId => createBannedRecipeInput(recipeId));
            }

            if (bannedRecipesContainer.childElementCount === 0) {
                createBannedRecipeInput(itemData.item_id);
            }

            document.getElementById('modal-shop-price').value = itemData.shop_price;
        } else {
            document.getElementById('modal-amount').value = itemData.amount;
        }

    } else {
        document.getElementById('modal-title').textContent = `Add New ${type.slice(0, -1)}`;
        document.getElementById('modal-item-id').value = '';

        if (type === 'unlocks') {
            createBannedRecipeInput();
        }
    }
}

function closeItemModal() {
    itemModal.classList.add('modal-hidden');
}

function openRecipeModal(unlockId) {
    recipeModal.classList.remove('modal-hidden');
    recipeForm.reset();
    document.getElementById('recipe-modal-unlock-id').value = unlockId;
    document.getElementById('recipe-ingredients-container').innerHTML = '';
    document.getElementById('recipe-results-container').innerHTML = '';

    const unlockData = window.currentMilestoneData.unlocks.find(u => u.id == unlockId);
    if (unlockData && unlockData.recipe) {
        const recipe = unlockData.recipe;

        document.getElementById('recipe-energy').value = recipe.energy;
        document.getElementById('recipe-time').value = recipe.time;

        recipe.ingredients.forEach(ing => addIngredientField(ing));

        if (Array.isArray(recipe.result)) {
            recipe.result.forEach(res => addResultField(res));
        } else {
            addResultField(recipe.result);
        }

    } else {
        addIngredientField();
        addResultField();
    }
}

function closeRecipeModal() {
    recipeModal.classList.add('modal-hidden');
}

function addIngredientField(ingredient = {}) {
    const container = document.getElementById('recipe-ingredients-container');
    const div = document.createElement('div');
    div.className = 'ingredient-field';

    const type = ingredient.tag ? 'tag' : 'item';
    const value = ingredient.tag || ingredient.item || '';

    div.innerHTML = `
        <select class="ingredient-type">
            <option value="item" ${type === 'item' ? 'selected' : ''}>Item</option>
            <option value="tag" ${type === 'tag' ? 'selected' : ''}>Tag</option>
        </select>
        <input type="text" class="ingredient-value" placeholder="e.g., minecraft:diamond" value="${value}" required>
        <input type="number" class="ingredient-count" min="1" value="${ingredient.count || 1}">
        <button type="button" class="remove-ingredient-btn">Remove</button>
    `;

    container.appendChild(div);

    div.querySelector('.remove-ingredient-btn').addEventListener('click', () => {
        div.remove();
    });
}

async function handleRecipeFormSubmit(e) {
    e.preventDefault();
    const unlockId = document.getElementById('recipe-modal-unlock-id').value;
    const token = await getApiToken();
    if (!token) return;

    const ingredients = [];
    document.querySelectorAll('.ingredient-field').forEach(field => {
        const type = field.querySelector('.ingredient-type').value;
        const value = field.querySelector('.ingredient-value').value;
        const count = parseInt(field.querySelector('.ingredient-count').value, 10);
        if (value) {
            ingredients.push({ [ type ]: value, count });
        }
    });

    const results = [];
    document.querySelectorAll('.result-field').forEach(field => {
        const item = field.querySelector('.result-item').value;
        const count = parseInt(field.querySelector('.result-count').value, 10);
        if (item) {
            results.push({ item, count });
        }
    });

    const recipeData = {
        ingredients,
        result: results,
        energy: parseInt(document.getElementById('recipe-energy').value, 10) || null,
        time: parseInt(document.getElementById('recipe-time').value, 10) || null,
    };

    try {
        const response = await fetch(`${baseUrl}/api/unlocks/${unlockId}/recipe`, {
            method: 'POST',
            headers: createApiHeaders(token),
            body: JSON.stringify(recipeData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to save recipe');
        }

        // Re-fetch milestone details to get updated recipe data
        showMilestonesInfo(milestones.find(m => m.id == window.currentMilestoneData.id));
        closeRecipeModal();
        alert('Recipe saved successfully!');
    } catch (error) {
        console.error('Save Recipe Error:', error);
        alert(`Error: ${error.message}`);
    }
}

function addResultField(result = {}) {
    const container = document.getElementById('recipe-results-container');
    const div = document.createElement('div');
    div.className = 'result-field';

    div.innerHTML = `
        <input type="text" class="result-item" placeholder="e.g., minecraft:diamond" value="${result.item || ''}" required>
        <input type="number" class="result-count" min="1" value="${result.count || 1}">
        <button type="button" class="remove-btn">Remove</button>
    `;

    container.appendChild(div);

    div.querySelector('.remove-btn').addEventListener('click', () => {
        div.remove();
    });
}


// --- Initialisation ---
document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();
    buildTrees();
});
