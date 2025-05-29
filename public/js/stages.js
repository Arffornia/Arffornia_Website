const canvas = document.querySelector(".canvas");

const milestoneInfo = document.querySelector(".info");
const infoTitle = milestoneInfo.querySelector("#title");
const infoDescription = milestoneInfo.querySelector("#description");
const infoStageNumber = milestoneInfo.querySelector("#stageNumber");
const infoPoints = milestoneInfo.querySelector("#reward_progress_points");
const infoIcon = milestoneInfo.querySelector(".icon");
const infoCloseBtnDiv = milestoneInfo.querySelector(".closeBtn")

import { getIconSvgByType } from "./mod_icons.js";

const { milestones, milestone_closure } = window.AppData;

// Manage cnavas mouvement (drag)
document.addEventListener("DOMContentLoaded", function () {
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
        if (node) {
            showNilestonesInfo(milestones.find(x => x.id == node.id));
        } else {
            hideMilestoneInfo();
        }
    });
});

// Hide the milestone info box
function hideMilestoneInfo() {
    milestoneInfo.classList.add("info-hidden");
    milestoneInfo.classList.remove("info-show");
}

// Show the milestone info box with the given milestone data
function showNilestonesInfo(milestone) {
    infoTitle.textContent = milestone.name;
    infoDescription.textContent = milestone.description;
    infoStageNumber.textContent = milestone.id; // TODO query stage id to get truth stage number
    infoPoints.textContent = milestone.reward_progress_points;
    infoIcon.innerHTML = getIconSvgByType(milestone.icon_type);

    milestoneInfo.classList.remove("info-hidden");
    milestoneInfo.classList.add("info-show");
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


const NODE_GAP = 70;

// Create a node element
function createNode(milestone) {
    const node = document.createElement("div");
    node.classList.add("node");
    node.id = milestone.id;
    node.title = milestone.name;

    node.style.position = 'absolute';
    node.style.left = `${milestone.x * NODE_GAP}px`;
    node.style.top = `${milestone.y * NODE_GAP}px`;

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

document.addEventListener("DOMContentLoaded", function () {
    buildTrees();
});

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

window.addEventListener("resize", () => {
    resizeCanvasToFitViewport();
});

