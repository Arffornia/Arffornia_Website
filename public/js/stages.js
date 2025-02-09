const canvas = document.querySelector(".canvas");

const milestoneInfo = document.querySelector(".info");
const infoTitle = milestoneInfo.querySelector("#title");
const infoDescription = milestoneInfo.querySelector("#description");
const infoStageNumber = milestoneInfo.querySelector("#stageNumber");
const infoPoints = milestoneInfo.querySelector("#reward_progress_points");
const infoIcon = milestoneInfo.querySelector(".icon");
const infoCloseBtnDiv = milestoneInfo.querySelector(".closeBtn")

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

    infoCloseBtnDiv.addEventListener('click', function() {
        hideMilestoneInfo();
    });

    canvas.addEventListener('click', function(event) {  
        const node = event.target.closest('.node');
        if (node) {
            showNilestonesInfo(milestones.find(x => x.id == node.id));
        } else {
            hideMilestoneInfo();
        }
    });
});

function hideMilestoneInfo() {
    milestoneInfo.classList.add("info-hidden");
    milestoneInfo.classList.remove("info-show");
}



function showNilestonesInfo(milestone) {
    infoTitle.textContent = milestone.name;
    infoDescription.textContent = milestone.description;
    infoStageNumber.textContent = milestone.id; // TODO query stage id to get truth stage number
    infoPoints.textContent = milestone.reward_progress_points;
    infoIcon.innerHTML = getIconSvgByType(milestone.icon_type);

    milestoneInfo.classList.remove("info-hidden");
    milestoneInfo.classList.add("info-show");
}


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
    node.id = milestone.id;
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
        linkNodes(closure.milestone_id, closure.descendant_id)
    })
}

document.addEventListener("DOMContentLoaded", function() {
    buildTrees();
});