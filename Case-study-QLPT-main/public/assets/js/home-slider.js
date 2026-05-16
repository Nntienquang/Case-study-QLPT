(function () {
    const slides = Array.from(document.querySelectorAll("[data-home-slide]"));
    const dots = Array.from(document.querySelectorAll("[data-home-dot]"));
    const prev = document.querySelector("[data-home-prev]");
    const next = document.querySelector("[data-home-next]");

    if (!slides.length) {
        return;
    }

    let active = 0;
    let timer = null;

    function show(index) {
        active = (index + slides.length) % slides.length;

        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle("is-active", slideIndex === active);
        });

        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle("is-active", dotIndex === active);
            dot.setAttribute("aria-pressed", dotIndex === active ? "true" : "false");
        });
    }

    function restart() {
        if (timer) {
            window.clearInterval(timer);
        }

        timer = window.setInterval(() => show(active + 1), 5500);
    }

    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            show(index);
            restart();
        });
    });

    if (prev) {
        prev.addEventListener("click", () => {
            show(active - 1);
            restart();
        });
    }

    if (next) {
        next.addEventListener("click", () => {
            show(active + 1);
            restart();
        });
    }

    show(0);
    restart();
})();
