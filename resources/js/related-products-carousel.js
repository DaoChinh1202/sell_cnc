export function initRelatedProductsCarousel(root) {
    const carousel = root.querySelector('[data-related-carousel]');
    if (!carousel || carousel.dataset.relatedReady) return;
    const track = carousel.querySelector('[data-related-track]');
    const controls = carousel.querySelector('[data-related-controls]');

    const previous = carousel.querySelector('[data-related-prev]');
    const next = carousel.querySelector('[data-related-next]');
    if (!track || !controls || !previous || !next) return;
    carousel.dataset.relatedReady = 'true';

    const cards = [...track.children];
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let visible = false;
    let overflowing = false;
    let hovered = carousel.matches(':hover');
    let focused = carousel.contains(document.activeElement);
    let timer;

    const advance = (direction) => {
        const maximum = track.scrollWidth - track.clientWidth;
        const current = track.scrollLeft;
        const bounds = track.getBoundingClientRect();
        const positions = cards.map(card => Math.min(maximum, Math.max(0,
            card.getBoundingClientRect().left - bounds.left + current)));
        const target = direction > 0
            ? (positions.find(position => position > current + 2) ?? 0)
            : (positions.reverse().find(position => position < current - 2) ?? maximum);
        track.scrollTo({ left: target, behavior: motion.matches ? 'instant' : 'smooth' });
    };

    const schedule = () => {
        clearTimeout(timer);
        if (motion.matches || !overflowing || !visible || document.hidden || hovered || focused) return;
        timer = setTimeout(() => {
            advance(1);
            schedule();
        }, 5000);
    };
    const stop = () => {
        // Restart the delay after manual input rather than disabling autoplay permanently.
        schedule();
    };
    const measure = () => {
        overflowing = track.scrollWidth > track.clientWidth + 2;
        controls.hidden = !overflowing;
        schedule();
    };

    previous.addEventListener('click', () => { stop(); advance(-1); });
    next.addEventListener('click', () => { stop(); advance(1); });

    // Listen to user input, not scroll events: autoplay also generates scroll events.
    track.addEventListener('pointerdown', stop, { passive: true });
    track.addEventListener('touchstart', stop, { passive: true });
    track.addEventListener('wheel', stop, { passive: true });
    track.addEventListener('click', stop);
    track.addEventListener('keydown', event => {
        stop();
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        advance(event.key === 'ArrowRight' ? 1 : -1);
    });
    carousel.addEventListener('pointerenter', event => {
        if (event.pointerType === 'touch') return;
        hovered = true;
        schedule();
    });
    carousel.addEventListener('pointerleave', () => { hovered = false; schedule(); });
    carousel.addEventListener('focusin', () => { focused = true; schedule(); });
    carousel.addEventListener('focusout', event => {
        focused = carousel.contains(event.relatedTarget);
        schedule();
    });
    document.addEventListener('visibilitychange', schedule);
    motion.addEventListener('change', () => {
        if (motion.matches) {

            track.scrollTo({ left: track.scrollLeft, behavior: 'instant' });
        }
        schedule();
    });

    // Without visibility observation, retain manual navigation rather than autoplay offscreen.
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            visible = entries[0].isIntersecting && entries[0].intersectionRatio > 0;
            schedule();
        });
        observer.observe(track);
    }
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(measure);
        observer.observe(track);
        cards.forEach(card => observer.observe(card));
    }
    window.addEventListener('resize', measure);
    measure();
}
