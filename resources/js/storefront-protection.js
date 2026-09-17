// These browser-side deterrents are not access control. In particular, the OS
// may take a screenshot without delivering a cancelable event to the page.
export function protectStorefront() {
    if (!document.body.classList.contains('storefront')) return;

    const cancel = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    const blockShortcut = (event) => {
        const key = event.key.toLowerCase();
        const isPrintScreen = key === 'printscreen' || event.code === 'PrintScreen';
        const isInspectShortcut = event.ctrlKey && event.shiftKey && key === 'i';

        if (key === 'f12' || isInspectShortcut || isPrintScreen) {
            cancel(event);
        }
    };

    document.addEventListener('keydown', blockShortcut, true);
    document.addEventListener('keyup', blockShortcut, true);
    document.addEventListener('contextmenu', cancel, true);

    document.querySelectorAll('img').forEach((image) => {
        image.draggable = false;
    });

    // Delegation also covers product images inserted after the page loads.
    document.addEventListener('dragstart', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        if (target.closest('img, picture') || target.closest('a')?.querySelector('img')) {
            cancel(event);
        }
    }, true);
}
