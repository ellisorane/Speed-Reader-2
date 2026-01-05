document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.querySelector(".nav-toggle");
    const menu = document.querySelector(".nav-menu");

    toggle.addEventListener("click", function () {
        menu.classList.toggle("active");
    });
});
