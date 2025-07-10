const canvas = document.querySelector(".canvas");

const milestoneInfo = document.querySelector(".info");
const infoTitle = milestoneInfo.querySelector("#title");
const infoDescription = milestoneInfo.querySelector("#description");
const infoStageNumber = milestoneInfo.querySelector("#stageNumber");
const infoPoints = milestoneInfo.querySelector("#reward_progress_points");
const infoIcon = milestoneInfo.querySelector("#iconContent");
const infoCloseBtnDiv = milestoneInfo.querySelector(".closeBtn")

import { getIconSvgByType } from "./mod_icons.js";

const { milestones, milestone_closure, isAdmin, csrfToken, baseUrl } = window.AppData;

var currentNodeId = null;

/**
 * Sets up all event listeners for the page.
 */
function setupEventListeners() {
    var dragging = false;
    var offsetX, offsetY;

    canvas.addEventListener("mousedown", function (e) {
        dragging = true;
        offsetX = e.clientX - parseInt(window.getComputedStyle(canvas).left);
        offsetY = e.clientY - parseInt(window.getComputedStyle(canvas).top);
    });

    document.addEventListener("mousemove", function (e) {
        if (dragging) {
            let newLeft = e.clientX - offsetX;
            let newTop = e.clientY - offsetY;

            const bgRect = document.querySelector(".bg").getBoundingClientRect();

            // Visible width and height of the viewport
            const visibleWidth = bgRect.width;
            const visibleHeight = bgRect.height;

            // Total width and height of the canvas
            const totalWidth = canvas.offsetWidth;
            const totalHeight = canvas.offsetHeight;

            // Clamp the canvas in the viewport
            newLeft = Math.min(0, Math.max(visibleWidth - totalWidth, newLeft));
            newTop = Math.min(0, Math.max(visibleHeight - totalHeight, newTop));

            canvas.style.left = `${newLeft}px`;
            canvas.style.top = `${newTop}px`;
        }
    });

    document.addEventListener("mouseup", function () {
        dragging = false;
    });

    infoCloseBtnDiv.addEventListener('click', function () {
        hideMilestoneInfo();
    });

    canvas.addEventListener('click', function (event) {
        const node = event.target.closest('.node');
        if (node && node.id !== currentNodeId) {
            showNilestonesInfo(milestones.find(x => x.id == node.id));
            currentNodeId = node.id;
        } else {
            hideMilestoneInfo();
            currentNodeId = null;
        }
    });

    window.addEventListener("resize", () => {
        resizeCanvasToFitViewport();
    });

    if (isAdmin) {
        exportBtn.addEventListener('click', handleExport);
        document.querySelector('.admin-actions').style.display = 'flex';
        editBtn.addEventListener('click', () => setEditMode(true));
        cancelBtn.addEventListener('click', () => setEditMode(false));
        saveBtn.addEventListener('click', handleSave);
    }
}

// Hide the milestone info box
function hideMilestoneInfo() {
    milestoneInfo.classList.remove("info-show");
}

// Show the milestone info box with the given milestone data
function showNilestonesInfo(milestone) {
    milestoneInfo.classList.add("info-show");
    const loader = document.getElementById("info-loader");
    const content = document.getElementById("info-content");

    loader.style.display = "block";
    content.style.display = "none";

    fetch(`${baseUrl}/api/milestone/get/${milestone.id}`)
        .then(response => {
            if (!response.ok) throw new Error("Erreur API");
            return response.json();
        })
        .then(data => {
            return new Promise(resolve => {
                setTimeout(() => resolve(data), 1000 * 0.3);
            });
        })
        .then(data => {
            currentMilestoneData = data;

            milestoneInfo.querySelector('#milestone-title').textContent = data.name;
            infoDescription.textContent = data.description;
            infoStageNumber.textContent = data.stage_id;
            infoPoints.textContent = data.reward_progress_points;
            infoIcon.innerHTML = getIconSvgByType(data.icon_type);
        })
        .catch(err => {
            console.error("Erreur lors du fetch :", err);
            milestoneInfo.querySelector('#milestone-title').textContent = "Erreur de chargement";
        })
        .finally(() => {
            loader.style.display = "none";
            content.style.display = "block";
            if (isAdmin) {
                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            }
        });
}

// Link two nodes together.
function linkNodes(nodeId1, nodeId2) {
    const node1 = document.getElementById(nodeId1);
    const node2 = document.getElementById(nodeId2);

    function getCenterRelativeToWindow(div) {
        const rect = div.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        return {
            x: rect.left - canvasRect.left + rect.width / 2,
            y: rect.top - canvasRect.top + rect.height / 2
        };
    }

    const center1 = getCenterRelativeToWindow(node1);
    const center2 = getCenterRelativeToWindow(node2);

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "100%");
    svg.setAttribute("height", "100%");
    svg.setAttribute("style", "position: absolute; top: 0; left: 0; z-index: -1;");

    const deltaY = center2.y - center1.y;

    if (deltaY === 0) {
        const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
        line.setAttribute("x1", center1.x);
        line.setAttribute("y1", center1.y);
        line.setAttribute("x2", center2.x);
        line.setAttribute("y2", center2.y);
        line.setAttribute("stroke", "black");
        line.setAttribute("fill", "none");
        line.classList.add("line");

        svg.appendChild(line);
    } else {
        const curveStrength = Math.min(Math.abs(deltaY) * 0.7, 100);

        const controlPoint = {
            x: (center1.x + center2.x) / 2,
            y: (center1.y + center2.y) / 2 + (deltaY > 0 ? curveStrength : -curveStrength)
        };

        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        const d = `M ${center1.x},${center1.y} Q ${controlPoint.x},${controlPoint.y} ${center2.x},${center2.y}`;
        path.setAttribute("d", d);
        path.setAttribute("fill", "none");
        path.setAttribute("stroke", "black");
        path.classList.add("line");

        svg.appendChild(path);
    }

    canvas.appendChild(svg);
}

const GRID_CELL_SPACING = 80; // Must correspond to --grid-large-size in the CSS
const NODE_CONTENT_DIAMETER = 70; // width/height of the .node in the CSS
const NODE_BORDER_WIDTH = 5;      // border-width of the .node in the CSS
const NODE_ACTUAL_DIAMETER = NODE_CONTENT_DIAMETER + (2 * NODE_BORDER_WIDTH); // Total size = 70 + 2*5 = 80

// Create a node element
function createNode(milestone) {
    const node = document.createElement("div");
    node.classList.add("node");
    node.id = milestone.id;
    node.title = milestone.name;

    node.style.position = 'absolute';

    const topLeftX = (milestone.x * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);
    const topLeftY = (milestone.y * GRID_CELL_SPACING) - (NODE_ACTUAL_DIAMETER / 2);

    node.style.left = `${topLeftX}px`;
    node.style.top = `${topLeftY}px`;

    const icon = document.createElement("div");
    icon.classList.add("icon");
    icon.innerHTML = getIconSvgByType(milestone.icon_type);

    node.appendChild(icon);
    return node;
}

// Build the graph by creating nodes and linking them
function buildTrees() {
    milestones.forEach(milestone => {
        canvas.appendChild(createNode(milestone))
    });

    milestone_closure.forEach(closure => {
        linkNodes(closure.milestone_id, closure.descendant_id)
    });

    resizeCanvasToFitViewport();
    centerCanvas();
}

// Resize the canvas to fit the canvas to the viewport
function resizeCanvasToFitViewport() {
    const canvas = document.querySelector(".canvas");
    const nodes = document.querySelectorAll(".node");

    let maxX = 0;
    let maxY = 0;

    nodes.forEach(node => {
        const x = parseInt(node.style.left || 0) + node.offsetWidth;
        const y = parseInt(node.style.top || 0) + node.offsetHeight;

        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
    });

    // Add some padding to ensure the canvas is larger than the window
    const padding = 200;
    const minWidth = Math.max(window.innerWidth, maxX + padding);
    const minHeight = Math.max(window.innerHeight, maxY + padding);

    canvas.style.width = `${minWidth}px`;
    canvas.style.height = `${minHeight}px`;
}

// Center the graph oln the middle for a better UX
function centerCanvas() {
    // const bgRect = document.querySelector(".bg").getBoundingClientRect();
    // const canvasRect = canvas.getBoundingClientRect();

    // const left = (bgRect.width - canvas.offsetWidth) / 2;
    // const top = (bgRect.height - canvas.offsetHeight) / 2;

    // canvas.style.left = `${Math.min(0, left)}px`;
    // canvas.style.top = `${Math.min(0, top)}px`;
}

const exportBtn = document.getElementById('exportStagesBtn');
let apiToken = null;

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
        alert("Authentication Error. Please refresh.");
        return null;
    }
}

async function handleExport() {
    const token = await getApiToken();
    if (!token) return;
    fetch(`${baseUrl}/api/stages/export`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(res => res.json())
        .then(data => {
            const blob = new Blob([ JSON.stringify(data, null, 2) ], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'stages-export.json';
            a.click();
            URL.revokeObjectURL(url);
        });
}

let currentMilestoneData = null;
let isEditing = false;

const editBtn = document.getElementById('editBtn');
const saveBtn = document.getElementById('saveBtn');
const cancelBtn = document.getElementById('cancelBtn');

function setEditMode(editing) {
    isEditing = editing;
    const titleContainer = milestoneInfo.querySelector('#milestone-title');
    const descriptionContainer = milestoneInfo.querySelector('#description');
    const pointsContainer = milestoneInfo.querySelector('#reward_progress_points');

    if (editing) {
        titleContainer.innerHTML = `<input type="text" class="title-input" value="${currentMilestoneData.name}">`;
        descriptionContainer.innerHTML = `<textarea class="description-input">${currentMilestoneData.description}</textarea>`;
        pointsContainer.innerHTML = `<input type="number" class="points-input" value="${currentMilestoneData.reward_progress_points}">`;

        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
    } else {
        titleContainer.innerHTML = currentMilestoneData.name;
        descriptionContainer.innerHTML = currentMilestoneData.description;
        pointsContainer.innerHTML = currentMilestoneData.reward_progress_points;

        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
    }
}

// Sauvegarde les modifications
async function handleSave() {
    const token = await getApiToken();
    if (!token) return;

    const updatedData = {
        name: milestoneInfo.querySelector('.title-input').value,
        description: milestoneInfo.querySelector('.description-input').value,
        reward_progress_points: parseInt(milestoneInfo.querySelector('.points-input').value, 10),
    };

    fetch(`${baseUrl}/api/milestones/${currentMilestoneData.id}`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(updatedData)
    })
        .then(res => {
            if (!res.ok) return Promise.reject('Save failed');
            return res.json();
        })
        .then(savedData => {
            currentMilestoneData = savedData;
            setEditMode(false);
        })
        .catch(error => console.error('Save Error:', error));
}


document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();
    buildTrees();
});
