import { getIconSvgByType } from "./mod_icons.js";

const canvas = document.querySelector(".canvas");
const milestoneInfo = document.querySelector(".info");
const infoCloseBtnDiv = milestoneInfo.querySelector(".closeBtn");

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

    // Main click handler for the canvas
    canvas.addEventListener('click', handleCanvasClick);

    // Info panel close button
    infoCloseBtnDiv.addEventListener('click', hideMilestoneInfo);

    window.addEventListener("resize", resizeCanvasToFitViewport);

    // Setup admin-specific listeners
    if (isAdmin) {
        setupAdminEventListeners();
    }
}

/**
 * Sets up event listeners for admin controls.
 */
function setupAdminEventListeners() {
    const exportBtn = document.getElementById('exportStagesBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', handleExport);
    }

    // Edit/Save/Cancel buttons in the info panel
    document.getElementById('editBtn').addEventListener('click', () => setEditMode(true));
    document.getElementById('cancelBtn').addEventListener('click', () => setEditMode(false));
    document.getElementById('saveBtn').addEventListener('click', handleSave);

    // Mode-switching buttons
    const adminModeButtons = document.querySelectorAll('.admin-mode-btn');
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
            showNilestonesInfo(milestones.find(m => m.id == clickedNodeEl.id));
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
        if (!response.ok) throw new Error(`Server responded with ${response.status}`);
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

// --- END: Admin Action Functions ---


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
    canvas.classList.remove('add-mode', 'delete-mode', 'link-mode', 'unlink-mode');
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
function showNilestonesInfo(milestone) {
    if (!milestone) return;

    milestoneInfo.classList.add("info-show");
    const loader = document.getElementById("info-loader");
    const content = document.getElementById("info-content");

    loader.style.display = "block";
    content.style.display = "none";
    setEditMode(false); // Ensure we are not in edit mode when showing new info

    fetch(`${baseUrl}/api/milestone/get/${milestone.id}`)
        .then(response => response.ok ? response.json() : Promise.reject('API Error'))
        .then(data => {
            return new Promise(resolve => {
                setTimeout(() => resolve(data), 1000 * 0.3);
            });
        })
        .then(data => {
            window.currentMilestoneData = data; // Store for editing
            milestoneInfo.querySelector('#milestone-title').textContent = data.name;
            milestoneInfo.querySelector("#description").textContent = data.description;
            milestoneInfo.querySelector("#stageNumber").textContent = data.stage_id;
            milestoneInfo.querySelector("#reward_progress_points").textContent = data.reward_progress_points;
            milestoneInfo.querySelector("#iconContent").innerHTML = getIconSvgByType(data.icon_type);

            const itemsContainer = milestoneInfo.querySelector("#itemsContainer ul");
            itemsContainer.innerHTML = '';

            if (data.unlocks && data.unlocks.length > 0) {
                data.unlocks.forEach(unlock => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <img src="${unlock.image_url}" alt="${unlock.display_name}" width="32" height="32" style="vertical-align: middle;">
                        <span>${unlock.display_name} (Prix: ${unlock.shop_price || 'N/A'})</span>
                    `;

                    itemsContainer.appendChild(li);
                });
            } else {
                itemsContainer.innerHTML = '<li>No items unlocked by this milestone.</li>';
            }
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
        a.download = 'stages-export.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (err) {
        console.error("Export failed", err);
        alert("Failed to export data.");
    }
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

    document.getElementById('editBtn').style.display = editing ? 'none' : 'inline-block';
    document.getElementById('saveBtn').style.display = editing ? 'inline-block' : 'none';
    document.getElementById('cancelBtn').style.display = editing ? 'inline-block' : 'none';

    if (editing) {
        titleContainer.innerHTML = `<input type="text" class="title-input" value="${window.currentMilestoneData.name}">`;
        descriptionContainer.innerHTML = `<textarea class="description-input">${window.currentMilestoneData.description}</textarea>`;
        pointsContainer.innerHTML = `<input type="number" class="points-input" value="${window.currentMilestoneData.reward_progress_points}">`;
    } else if (window.currentMilestoneData) {
        titleContainer.textContent = window.currentMilestoneData.name;
        descriptionContainer.textContent = window.currentMilestoneData.description;
        pointsContainer.textContent = window.currentMilestoneData.reward_progress_points;
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

// --- Initialisation ---
document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();
    buildTrees();
});
