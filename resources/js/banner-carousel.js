export function initBannerCarousel() {
    document.querySelectorAll('[data-banner-carousel]').forEach((carousel) => {
        const track = carousel.querySelector('[data-banner-track]');
        const slides = [...track.children];
        if (slides.length < 2) return;

        const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        let current = 0;
        let position = 1;
        let busy = false;
        let timer;
        let touchStart;

        // End copies let the carousel wrap without sliding backwards through the track.
        const clone = (slide) => {
            const copy = slide.cloneNode(true);
            copy.setAttribute('aria-hidden', 'true');
            copy.inert = true;
            return copy;
        };
        track.prepend(clone(slides.at(-1)));
        track.append(clone(slides[0]));

        const moveTrack = (animate) => {
            track.style.transition = animate && !motion.matches ? 'transform 500ms ease' : 'none';
            track.style.transform = `translateX(-${position * 100}%)`;
        };
        const update = () => {
            slides.forEach((slide, index) => {
                slide.setAttribute('aria-hidden', String(index !== current));
                slide.inert = index !== current;
            });
        };
        const schedule = () => {
            clearTimeout(timer);
            if (!document.hidden) {
                timer = setTimeout(() => show(current + 1), 5000);
            }
        };
        const finish = () => {
            position = current + 1;
            moveTrack(false);
            busy = false;
        };
        const show = (index) => {
            if (busy) return;
            position = index + 1;
            current = (index + slides.length) % slides.length;
            busy = !motion.matches;
            moveTrack(true);
            update();
            if (motion.matches) finish();
            schedule();
        };
        track.addEventListener('transitionend', (event) => {
            if (event.target === track && event.propertyName === 'transform') finish();
        });
        document.addEventListener('visibilitychange', () => { finish(); schedule(); });
        motion.addEventListener('change', () => { finish(); schedule(); });
        carousel.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            show(current + (event.key === 'ArrowRight' ? 1 : -1));
        });
        track.addEventListener('touchstart', (event) => {
            const touch = event.touches[0];
            touchStart = { x: touch.clientX, y: touch.clientY };
            clearTimeout(timer);
        }, { passive: true });
        track.addEventListener('touchend', (event) => {
            if (!touchStart) return;
            const touch = event.changedTouches[0];
            const dx = touch.clientX - touchStart.x;
            const dy = touch.clientY - touchStart.y;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) show(current + (dx < 0 ? 1 : -1));
            touchStart = null;
            schedule();
        }, { passive: true });
        track.addEventListener('touchcancel', () => { touchStart = null; schedule(); }, { passive: true });
        moveTrack(false);
        update();
        schedule();
    });
}
