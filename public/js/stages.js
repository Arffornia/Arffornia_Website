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

function createNode(milestone) {
    const node = document.createElement("div");   
    node.classList.add("node");
    node.id = `node_${milestone.id}`;
    node.title = milestone.name;

    const icon = document.createElement("div"); 
    icon.classList.add("icon");
    icon.innerHTML = getIconSvgByType(milestone.icon_type);

    node.appendChild(icon);
    return node;
}

function buildTree(milestone) {
    const nodeContainer = document.createElement("div");   
    const nodeChildren = document.createElement("div");  
    
    nodeContainer.classList.add("nodeContainer");
    nodeChildren.classList.add("nodeChildren");

    nodeContainer.appendChild(createNode(milestone));
    
    milestone_closure.forEach(closure => {
        if(closure.milestone_id == milestone.id) {
            nodeChildren.appendChild(buildTree(
                milestones.find(x => x.id == closure.descendant_id)
            ))
        }
    });

    nodeContainer.appendChild(nodeChildren);
    
    return nodeContainer;
}

function buildTrees() {
    milestones.forEach(milestone => {
        if(milestone.is_root) {
            const tree = buildTree(milestone);
            canvas.appendChild(tree);
        }
    })

    milestone_closure.forEach(closure => {
        linkNodes(`node_${closure.milestone_id}`, `node_${closure.descendant_id}`)
    })
}

document.addEventListener("DOMContentLoaded", function() {
    buildTrees();
});