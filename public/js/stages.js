const canvas = document.querySelector(".canvas");

document.addEventListener("DOMContentLoaded", function() {
    var dragging = false;
    var offsetX, offsetY;

    canvas.addEventListener("mousedown", function(e) {
        dragging = true;
        offsetX = e.clientX - parseInt(window.getComputedStyle(canvas).left);
        offsetY = e.clientY - parseInt(window.getComputedStyle(canvas).top);
    });

    document.addEventListener("mousemove", function(e) {
        if (dragging) {
            canvas.style.left = e.clientX - offsetX + "px";
            canvas.style.top = e.clientY - offsetY + "px";
        }
    });

    document.addEventListener("mouseup", function() {
        dragging = false;
    });
});


function getCenterRelativeToWindow(div) {
    const rect = div.getBoundingClientRect();
    const canvasRect = canvas.getBoundingClientRect();
    return {
        x: rect.left - canvasRect.left + rect.width / 2,
        y: rect.top - canvasRect.top + rect.height / 2
    };
}


function linkNodes(nodeId1, nodeId2) {
    const node1 = document.getElementById(nodeId1);
    const node2 = document.getElementById(nodeId2);

    const center1 = getCenterRelativeToWindow(node1);
    const center2 = getCenterRelativeToWindow(node2);

    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", "100%");
    svg.setAttribute("height", "100%");
    svg.setAttribute("style", "position: absolute; top: 0; left: 0; z-index: -1;");

    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.setAttribute("x1", center1.x);
    line.setAttribute("y1", center1.y);
    line.setAttribute("x2", center2.x);
    line.setAttribute("y2", center2.y);
    line.classList.add("line");

    svg.appendChild(line);
    canvas.appendChild(svg);
}

function createLayer(layerId) {
    const newLayer = document.createElement("div");
    
    newLayer.classList.add("layer");
    newLayer.id = layerId;

    canvas.appendChild(newLayer);
}

function createNode(nodeId, layerId) {
    const newNode = document.createElement("div");

    newNode.classList.add("node");
    newNode.id = nodeId;

    const layer = document.getElementById(layerId);
    layer.appendChild(newNode);
}

function createLayerLine() {
    const newLayerLine = document.createElement("div");
    newLayerLine.classList.add("layer_line");
    canvas.appendChild(newLayerLine);
}

function createStages() {
    const sortedStages = stages.sort((a, b) => a.number - b.number);

    sortedStages.forEach(stage => {
        createLayer(`layer_${stage.id}`);
    });
}

function createNodes() {
    milestones.forEach(milestone => {
        createNode(`node_${milestone.id}`, `layer_${milestone.stage_id}`);
    });
}

function createNodeLinks() {
    milestone_closure.forEach(closure => {
        linkNodes(`node_${closure.milestone_id}`, `node_${closure.descendant_id}`)
    })
}

document.addEventListener("DOMContentLoaded", function() {
    createStages();
    createNodes();
    createNodeLinks();
});
