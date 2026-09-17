export function initProductImageZoom() {
    const hoverPointer = window.matchMedia('(hover: hover) and (pointer: fine)');

    document.querySelectorAll('.product-detail__gallery').forEach((gallery) => {
        const image = gallery.querySelector('img');
        if (!image) return;

        let frame = null;
        let pointer = null;

        const reset = () => {
            if (frame !== null) cancelAnimationFrame(frame);
            frame = null;
            pointer = null;
            gallery.classList.remove('is-zoomed');
            // Keep the last origin while scaling back to avoid a sideways jump.
        };

        const update = () => {
            frame = null;
            if (!pointer || !hoverPointer.matches || !image.complete || !image.naturalWidth) {
                reset();
                return;
            }

            // Measure the fixed frame, not the transformed image, so zooming
            // cannot feed back into the pointer coordinates and cause jitter.
            const bounds = gallery.getBoundingClientRect();
            if (!bounds.width || !bounds.height) return;
            const x = Math.max(0, Math.min(100, (pointer.x - bounds.left) / bounds.width * 100));
            const y = Math.max(0, Math.min(100, (pointer.y - bounds.top) / bounds.height * 100));

            gallery.style.setProperty('--image-zoom-x', `${x}%`);
            gallery.style.setProperty('--image-zoom-y', `${y}%`);
            gallery.classList.add('is-zoomed');
        };

        const track = (event) => {
            if (!hoverPointer.matches || event.pointerType === 'touch') {
                reset();
                return;
            }

            pointer = { x: event.clientX, y: event.clientY };
            if (!gallery.classList.contains('is-zoomed')) {
                update();
            } else if (frame === null) {
                frame = requestAnimationFrame(update);
            }
        };

        gallery.classList.add('has-image-zoom');
        gallery.addEventListener('pointerenter', track);
        gallery.addEventListener('pointermove', track);
        gallery.addEventListener('pointerleave', reset);
        gallery.addEventListener('pointercancel', reset);
        image.addEventListener('error', reset);
        hoverPointer.addEventListener('change', reset);
        window.addEventListener('blur', reset);
        window.addEventListener('resize', reset);
        window.addEventListener('scroll', reset, { capture: true, passive: true });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) reset();
        });
    });
}
