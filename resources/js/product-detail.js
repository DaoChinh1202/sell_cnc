async function copySku(value) {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(value);
            return true;
        }
    } catch {
        // Clipboard permissions or insecure contexts may require the legacy path.
    }

    const focused = document.activeElement;
    const selection = window.getSelection();
    const ranges = selection ? Array.from({ length: selection.rangeCount }, (_, index) => selection.getRangeAt(index).cloneRange()) : [];
    const input = document.createElement('textarea');
    input.value = value;
    input.readOnly = true;
    input.setAttribute('aria-label', 'Mã mẫu cần sao chép');
    input.style.cssText = 'position:fixed;left:-9999px;top:0;font-size:16px;';
    document.body.append(input);
    try {
        input.focus({ preventScroll: true });
        input.select();
        input.setSelectionRange(0, value.length);
        return document.execCommand('copy') === true;
    } catch {
        return false;
    } finally {
        input.remove();
        focused?.focus({ preventScroll: true });
        if (selection) {
            selection.removeAllRanges();
            ranges.forEach(range => selection.addRange(range));
        }
    }
}

export function initProductDetail() {
    const root = document.querySelector('[data-product-detail]');
    if (!root) return;

    const copyButton = root.querySelector('[data-copy-sku]');
    const status = root.querySelector('[data-copy-status]');
    if (copyButton && status) {
        copyButton.hidden = false;
        copyButton.addEventListener('click', async () => {
            copyButton.disabled = true;
            status.textContent = 'Đang sao chép…';
            try {
                const copied = await copySku(copyButton.dataset.copySku);
                status.textContent = copied ? 'Đã sao chép mã mẫu.' : 'Không thể sao chép tự động. Vui lòng chọn và sao chép mã mẫu ở trên.';
            } finally {
                copyButton.disabled = false;
            }
        });
    }

    const dialog = root.querySelector('#product-image-dialog');
    const openButton = root.querySelector('[data-image-open]');
    root.querySelectorAll('[data-detail-image]').forEach(image => {
        const showFallback = () => {
            image.hidden = true;
            const fallback = image.parentElement.querySelector('[data-image-fallback]');
            if (fallback) fallback.hidden = false;
            if (image.closest('.product-detail__gallery') && openButton) openButton.hidden = true;
        };
        image.addEventListener('error', showFallback);
        if (image.complete && !image.naturalWidth) showFallback();
    });

    if (!dialog || !openButton || typeof dialog.showModal !== 'function') return;
    const mainImage = root.querySelector('.product-detail__gallery img');
    openButton.hidden = !mainImage || mainImage.hidden;
    let returnFocus;
    openButton.addEventListener('click', () => {
        if (dialog.open) return;
        returnFocus = document.activeElement;
        dialog.showModal();
        document.documentElement.classList.add('product-image-dialog-open');
    });
    dialog.querySelector('[data-image-close]').addEventListener('click', () => dialog.close());
    // Native dialog handles Escape, background inertness, and keyboard focus trapping.
    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove('product-image-dialog-open');
        returnFocus?.focus({ preventScroll: true });
    });
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
}
