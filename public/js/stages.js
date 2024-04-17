document.addEventListener("DOMContentLoaded", function() {
    var fenetre = document.querySelector(".window");
    var dragging = false;
    var offsetX, offsetY;

    fenetre.addEventListener("mousedown", function(e) {
        dragging = true;
        offsetX = e.clientX - parseInt(window.getComputedStyle(fenetre).left);
        offsetY = e.clientY - parseInt(window.getComputedStyle(fenetre).top);
    });

    document.addEventListener("mousemove", function(e) {
        if (dragging) {
            fenetre.style.left = e.clientX - offsetX + "px";
            fenetre.style.top = e.clientY - offsetY + "px";
        }
    });

    document.addEventListener("mouseup", function() {
        dragging = false;
    });
});


function getCenterRelativeToWindow(div) {
    const rect = div.getBoundingClientRect();
    const windowRect = document.querySelector('.window').getBoundingClientRect();
    return {
        x: rect.left - windowRect.left + rect.width / 2,
        y: rect.top - windowRect.top + rect.height / 2
    };
}


function linkNodes(node1, node2) {
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
    document.querySelector('.window').appendChild(svg);
}

const node1 = document.getElementById('node_1');
const node2 = document.getElementById('node_2');
linkNodes(node1, node2);


