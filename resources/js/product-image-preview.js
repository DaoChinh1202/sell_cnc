const initializedRoots = new WeakSet();

export function initProductImagePreviews() {
    document.querySelectorAll('[data-product-image-preview]').forEach(root => {
        if (initializedRoots.has(root)) return;
        const input = document.getElementById(root.dataset.inputId);
        const watermark = input?.form?.querySelector('#productWatermark');
        const panel = root.querySelector('[data-preview-panel]');
        const image = root.querySelector('[data-preview-image]');
        const status = root.querySelector('[data-preview-status]');
        const label = root.querySelector('[data-preview-label]');
        const clearButton = root.querySelector('[data-preview-clear]');
        const retryButton = root.querySelector('[data-preview-retry]');
        if (!input || !panel || !image || !status || !label || !clearButton || !retryButton) return;

        initializedRoots.add(root);
        let objectUrl = null;
        let controller = null;
        let activeLoader = null;
        let revision = 0;
        const disposeLoader = () => {
            if (!activeLoader) return;
            activeLoader.onload = null;
            activeLoader.onerror = null;
            activeLoader.removeAttribute('src');
            activeLoader = null;
        };
        const clear = () => {
            revision++;
            controller?.abort();
            controller = null;
            disposeLoader();
            panel.hidden = true;
            image.removeAttribute('src');
            label.textContent = '';
            status.textContent = '';
            retryButton.hidden = true;
            clearButton.hidden = !input.files?.length;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        };
        const fail = message => {
            clear();
            status.textContent = message;
            retryButton.hidden = false;
        };
        const preview = async () => {
            clear();
            const file = input.files?.[0];
            if (!file) return;
            let validationError;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                validationError = 'Vui lòng chọn ảnh JPEG, PNG hoặc WebP.';
            } else if (file.size > 5 * 1024 * 1024) {
                validationError = 'Ảnh vượt quá 5 MB. Vui lòng chọn ảnh nhỏ hơn.';
            }
            if (validationError) {
                input.value = '';
                clear();
                status.textContent = validationError;
                return;
            }

            const currentRevision = revision;
            const applyWatermark = watermark?.checked ?? true;
            const processedLabel = `Ảnh đã xử lý trên máy chủ · ${applyWatermark ? 'Có watermark' : 'Không watermark'}`;
            controller = new AbortController();
            status.textContent = 'Đang tải lên và xử lý ảnh xem trước…';
            try {
                const body = new FormData();
                body.append('image', file);
                body.append('apply_watermark', applyWatermark ? '1' : '0');
                const token = input.form?.querySelector('[name="_token"]')?.value
                    || document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(root.dataset.previewUrl, {
                    method: 'POST',
                    body,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', ...(token ? { 'X-CSRF-TOKEN': token } : {}) },
                    signal: controller.signal,
                });
                if (currentRevision !== revision) return;
                if (!response.ok) {
                    const messages = {
                        401: 'Vui lòng đăng nhập lại để xem trước ảnh.',
                        403: 'Bạn không có quyền xem trước ảnh.',
                        419: 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang trước khi thử lại.',
                        422: 'Ảnh không hợp lệ. Ảnh phải có kích thước từ 100 đến 6000 px và không quá 5 MB.',
                        429: 'Bạn gửi quá nhiều yêu cầu. Vui lòng chờ rồi thử lại.',
                    };
                    let message = messages[response.status] || (response.status >= 500
                        ? 'Máy chủ không thể xử lý ảnh xem trước. Vui lòng thử lại.'
                        : `Không thể xem trước ảnh (HTTP ${response.status}).`);
                    if (response.status === 422) {
                        const data = await response.json().catch(() => null);
                        const errors = Object.values(data?.errors || {}).flat().filter(value => typeof value === 'string');
                        if (errors.length) message += ` ${errors.join(' ')}`;
                    }
                    throw new Error(message);
                }
                if (!response.headers.get('Content-Type')?.toLowerCase().startsWith('image/')) {
                    throw new Error('Máy chủ không trả về ảnh xem trước. Vui lòng kiểm tra phiên đăng nhập rồi thử lại.');
                }
                const blob = await response.blob();
                if (currentRevision !== revision) return;
                objectUrl = URL.createObjectURL(blob);
                const loader = new Image();
                activeLoader = loader;
                loader.onload = () => {
                    if (currentRevision !== revision || activeLoader !== loader) return;
                    image.src = objectUrl;
                    label.textContent = processedLabel;
                    panel.hidden = false;
                    status.textContent = `${file.name} · ${loader.naturalWidth} × ${loader.naturalHeight} px · ${processedLabel}`;
                    disposeLoader();
                };
                loader.onerror = () => {
                    if (currentRevision !== revision || activeLoader !== loader) return;
                    fail('Không đọc được ảnh đã xử lý từ máy chủ. Vui lòng thử lại.');
                };
                loader.src = objectUrl;
            } catch (error) {
                if (currentRevision !== revision) return;
                fail(error instanceof TypeError
                    ? 'Không thể kết nối để xem trước ảnh. Vui lòng kiểm tra mạng và thử lại.'
                    : error.message || 'Không thể xem trước ảnh. Vui lòng thử lại.');
            }
        };

        input.addEventListener('change', preview);
        watermark?.addEventListener('change', preview);
        retryButton.addEventListener('click', preview);
        clearButton.addEventListener('click', () => {
            input.value = '';
            clear();
            input.focus();
        });
        input.form?.addEventListener('reset', event => {
            // Wait until the browser restores form controls; respect canceled resets.
            setTimeout(() => { if (!event.defaultPrevented) clear(); }, 0);
        });
        window.addEventListener('pagehide', clear);
        window.addEventListener('pageshow', event => {
            if (event.persisted && input.files?.length) preview();
        });
        preview();
    });
}
