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
