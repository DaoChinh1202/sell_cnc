import test from 'node:test';
import assert from 'node:assert/strict';
import { initProductImagePreviews } from '../../resources/js/product-image-preview.js';

const MAX_SIZE = 5 * 1024 * 1024;
const file = (overrides = {}) => ({ name: 'machine.png', type: 'image/png', size: 1024, ...overrides });
const tick = async () => { for (let i = 0; i < 10; i++) await Promise.resolve(); };
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
};
const response = (status = 200, type = 'image/webp', data = {}) => ({
    ok: status >= 200 && status < 300, status,
    headers: { get: name => name === 'Content-Type' ? type : null },
    json: async () => data,
    blob: async () => new Blob(['processed'], { type }),
});

class ElementMock {
    hidden = true;
    textContent = '';
    listeners = new Map();
    set innerHTML(value) { assert.fail(`Preview must not render HTML: ${value}`); }
    addEventListener(type, callback) {
        const callbacks = this.listeners.get(type) ?? [];
        callbacks.push(callback);
        this.listeners.set(type, callbacks);
    }
    dispatch(type, event = {}) {
        for (const callback of this.listeners.get(type) ?? []) callback(event);
    }
    removeAttribute(name) { delete this[name]; }
}

function setup(t, { formToken = 'form-token', metaToken = 'meta-token' } = {}) {
    const input = new ElementMock();
    const form = new ElementMock();
    const watermark = new ElementMock();
    watermark.checked = true;
    const elements = Object.fromEntries(['panel', 'image', 'status', 'label', 'clear', 'retry']
        .map(name => [name, new ElementMock()]));
    const loaders = [], created = [], revoked = [], timers = [], requests = [];
    input.files = [];
    input.form = form;
    input.focus = () => { input.focused = true; };
    let value = '';
    Object.defineProperty(input, 'value', {
        get: () => value,
        set: next => { value = next; if (next === '') input.files = []; },
    });
    form.querySelector = selector => ({
        '#productWatermark': watermark,
        '[name="_token"]': formToken ? { value: formToken } : null,
    })[selector];
    const root = {
        dataset: { inputId: 'productImage', previewUrl: '/admin/products/image-preview' },
        querySelector: selector => elements[selector.slice(14, -1)],
    };
    const window = new ElementMock();
    const globals = {
        window,
        document: {
            querySelectorAll: selector => {
                assert.equal(selector, '[data-product-image-preview]');
                return [root];
            },
            getElementById: id => { assert.equal(id, 'productImage'); return input; },
            querySelector: selector => {
                assert.equal(selector, 'meta[name="csrf-token"]');
                return metaToken ? { content: metaToken } : null;
            },
        },
        FormData: class {
            values = new Map();
            append(name, value) { this.values.set(name, value); }
            get(name) { return this.values.get(name); }
        },
        fetch: (url, options) => {
            const request = { url, ...options, ...deferred() };
            requests.push(request);
            return request.promise; // Deliberately ignores abort to exercise revision guards.
        },
        Image: class extends ElementMock {
            naturalWidth = 800;
            naturalHeight = 600;
            constructor() { super(); loaders.push(this); }
        },
        URL: {
            createObjectURL: blob => {
                const url = `blob:preview-${created.length + 1}`;
                created.push({ blob, url });
                return url;
            },
            revokeObjectURL: url => revoked.push(url),
        },
        setTimeout: (callback, delay) => {
            assert.equal(delay, 0);
            timers.push(callback);
        },
    };
    for (const [name, replacement] of Object.entries(globals)) {
        const descriptor = Object.getOwnPropertyDescriptor(globalThis, name);
        Object.defineProperty(globalThis, name, { configurable: true, writable: true, value: replacement });
        t.after(() => {
            if (descriptor) Object.defineProperty(globalThis, name, descriptor);
            else delete globalThis[name];
        });
    }
    initProductImagePreviews();
    return {
        input, form, watermark, window, ...elements, loaders, created, revoked, requests,
        select(selected = file()) {
            input.value = `C:\\fakepath\\${selected.name}`;
            input.files = [selected];
            input.dispatch('change');
            return requests.at(-1);
        },
        async finish(request = requests.at(-1)) {
            request.resolve(response());
            await tick();
            loaders.at(-1).onload();
        },
        flushTimers() { for (const callback of timers.splice(0)) callback(); },
    };
}

function assertDisposed(loader) {
    assert.equal(loader.onload, null);
    assert.equal(loader.onerror, null);
    assert.equal(loader.src, undefined);
}

function assertHidden(h) {
    assert.equal(h.panel.hidden, true);
    assert.equal(h.image.src, undefined);
}

for (const type of ['image/jpeg', 'image/png', 'image/webp']) {
    test(`uploads ${type} at 5 MB and displays only the processed blob`, async t => {
        const h = setup(t);
        const selected = file({ type, size: MAX_SIZE, name: '<img src=x onerror=alert(1)>.png' });
        assert.equal(h.requests.length, 0);
        const request = h.select(selected);
        assert.equal(request.url, '/admin/products/image-preview');
        assert.equal(request.method, 'POST');
        assert.equal(request.credentials, 'same-origin');
        assert.deepEqual(request.headers, { Accept: 'application/json', 'X-CSRF-TOKEN': 'form-token' });
        assert.equal(request.body.get('image'), selected);
        assert.equal(request.body.get('apply_watermark'), '1');
        assertHidden(h);
        assert.equal(h.created.length, 0);
        await h.finish();
        assert.equal(h.panel.hidden, false);
        assert.equal(h.image.src, 'blob:preview-1');
        assertDisposed(h.loaders[0]);
        assert.equal(await h.created[0].blob.text(), 'processed');
        assert.match(h.status.textContent, /800 × 600 px/);
        assert.ok(h.status.textContent.includes(selected.name));
        assert.match(h.label.textContent, /Có watermark/);
        assert.equal(h.input.files[0], selected);
    });
}

test('toggle uploads both off and on, revokes old preview, and uses meta CSRF fallback', async t => {
    const h = setup(t, { formToken: '' });
    h.watermark.dispatch('change');
    assert.equal(h.requests.length, 0);
    h.select();
    await h.finish();
    h.watermark.checked = false;
    h.watermark.dispatch('change');
    assertHidden(h);
    assert.deepEqual(h.revoked, ['blob:preview-1']);
    assert.equal(h.requests[1].body.get('apply_watermark'), '0');
    assert.equal(h.requests[1].headers['X-CSRF-TOKEN'], 'meta-token');
    await h.finish();
    assert.match(h.label.textContent, /Không watermark/);
    h.watermark.checked = true;
    h.watermark.dispatch('change');
    assert.equal(h.requests[2].body.get('apply_watermark'), '1');
    await h.finish();
    assert.match(h.label.textContent, /Có watermark/);
});

test('replacement and toggle abort pending fetches; stale success and failure cannot overwrite', async t => {
    const h = setup(t);
    const first = h.select(file({ name: 'old.png' }));
    const second = h.select(file({ name: 'new.png' }));
    assert.equal(first.signal.aborted, true);
    h.watermark.checked = false;
    h.watermark.dispatch('change');
    assert.equal(second.signal.aborted, true);
    await h.finish();
    first.resolve(response());
    second.reject(new TypeError('offline'));
    await tick();
    assert.equal(h.created.length, 1);
    assert.match(h.status.textContent, /new.png/);
    assert.match(h.label.textContent, /Không watermark/);
    assert.equal(h.retry.hidden, true);
});

test('stale blob reads and image decode callbacks are ignored', async t => {
    const h = setup(t);
    const blob = deferred();
    h.select().resolve({ ...response(), blob: () => blob.promise });
    await tick();
    h.select();
    blob.resolve(new Blob(['old']));
    await tick();
    assert.equal(h.created.length, 0);
    h.requests.at(-1).resolve(response());
    await tick();
    const oldLoader = h.loaders[0];
    const staleLoad = oldLoader.onload;
    const staleError = oldLoader.onerror;
    h.select();
    assertDisposed(oldLoader);
    staleLoad();
    staleError();
    assertHidden(h);
    assert.equal(h.retry.hidden, true);
    await h.finish();
    assert.deepEqual(h.revoked, ['blob:preview-1']);
});

for (const [name, overrides] of [
    ['nonimage', { type: 'text/plain' }],
    ['unsupported image', { type: 'image/svg+xml' }],
    ['over 5 MB', { size: MAX_SIZE + 1 }],
]) {
    test(`rejects ${name} locally and clears previous preview`, async t => {
        const h = setup(t);
        h.select();
        await h.finish();
        h.select(file(overrides));
        assertHidden(h);
        assert.equal(h.input.value, '');
        assert.equal(h.requests.length, 1);
        assert.equal(h.retry.hidden, true);
        assert.deepEqual(h.revoked, ['blob:preview-1']);
    });
}

for (const [code, message] of [
    [401, /đăng nhập/], [403, /không có quyền/], [419, /hết hạn/],
    [422, /100 đến 6000/], [429, /quá nhiều/], [500, /Máy chủ/], [503, /Máy chủ/],
]) {
    test(`HTTP ${code} preserves selection, reports failure, and permits retry`, async t => {
        const h = setup(t);
        const selected = file();
        h.select(selected).resolve(response(code, 'application/json', { errors: { image: ['Invalid dimensions'] } }));
        await tick();
        assertHidden(h);
        assert.equal(h.created.length, 0);
        assert.equal(h.input.files[0], selected);
        assert.match(h.status.textContent, message);
        if (code === 422) assert.match(h.status.textContent, /Invalid dimensions/);
        assert.equal(h.retry.hidden, false);
        h.retry.dispatch('click');
        await h.finish();
        assert.equal(h.panel.hidden, false);
        assert.equal(h.retry.hidden, true);
    });
}

test('non-image success (including login HTML) never displays original as processed', async t => {
    const h = setup(t);
    h.select().resolve(response(200, 'text/html'));
    await tick();
    assertHidden(h);
    assert.match(h.status.textContent, /không trả về ảnh/);
    assert.equal(h.created.length, 0);
    assert.equal(h.input.files.length, 1);
    assert.equal(h.retry.hidden, false);
});

test('network and processed-image decode failures preserve file for retry/save', async t => {
    const h = setup(t);
    h.select().reject(new TypeError('Failed to fetch'));
    await tick();
    assert.match(h.status.textContent, /kiểm tra mạng/);
    assert.equal(h.input.files.length, 1);
    h.retry.dispatch('click');
    h.requests.at(-1).resolve(response());
    await tick();
    const loader = h.loaders.at(-1);
    const staleLoad = loader.onload;
    loader.onerror();
    assertDisposed(loader);
    staleLoad();
    assertHidden(h);
    assert.equal(h.input.files.length, 1);
    assert.equal(h.retry.hidden, false);
    assert.deepEqual(h.revoked, ['blob:preview-1']);
});

test('clear revokes displayed blob, aborts pending request, and prevents stale completion', async t => {
    const h = setup(t);
    h.select();
    await h.finish();
    h.clear.dispatch('click');
    assertHidden(h);
    assert.equal(h.input.value, '');
    assert.equal(h.input.focused, true);
    assert.deepEqual(h.revoked, ['blob:preview-1']);
    const pending = h.select();
    h.clear.dispatch('click');
    assert.equal(pending.signal.aborted, true);
    pending.resolve(response());
    await tick();
    assert.equal(h.created.length, 1);
    assert.equal(h.status.textContent, '');
    assert.equal(h.clear.hidden, true);
    assert.equal(h.retry.hidden, true);
});

test('reset waits for native clearing, revokes blobs and aborts pending uploads', async t => {
    const h = setup(t);
    h.select();
    await h.finish();
    h.form.dispatch('reset', { defaultPrevented: false });
    assert.equal(h.panel.hidden, false);
    h.input.value = '';
    h.flushTimers();
    assertHidden(h);
    assert.deepEqual(h.revoked, ['blob:preview-1']);
    const pending = h.select();
    h.form.dispatch('reset', { defaultPrevented: false });
    h.input.value = '';
    h.flushTimers();
    assert.equal(pending.signal.aborted, true);
    pending.reject(new DOMException('Aborted', 'AbortError'));
    await tick();
    assert.equal(h.status.textContent, '');
    assert.equal(h.retry.hidden, true);
    assert.equal(h.requests.length, 2);
});

test('repeated replacements and toggles keep at most one live blob', async t => {
    const h = setup(t);
    for (let i = 0; i < 12; i++) {
        if (i % 2 === 0) h.select(file({ name: `machine-${i}.png` }));
        else {
            h.watermark.checked = !h.watermark.checked;
            h.watermark.dispatch('change');
        }
        assert.equal(h.created.length, h.revoked.length);
        await h.finish();
        assertDisposed(h.loaders.at(-1));
        assert.equal(h.created.length - h.revoked.length, 1);
    }
    h.clear.dispatch('click');
    assert.deepEqual(h.revoked, h.created.map(({ url }) => url));
});

for (const phase of ['fetch', 'decode', 'display']) {
    test(`pagehide during ${phase} cleans up without losing selection; BFCache restores preview`, async t => {
        const h = setup(t);
        const selected = file();
        const request = h.select(selected);
        let staleLoad, staleError;
        if (phase !== 'fetch') {
            request.resolve(response());
            await tick();
            staleLoad = h.loaders[0].onload;
            staleError = h.loaders[0].onerror;
            if (phase === 'display') staleLoad();
        }
        h.window.dispatch('pagehide', { persisted: true });
        assert.equal(request.signal.aborted, true);
        assertHidden(h);
        assert.equal(h.status.textContent, '');
        assert.equal(h.label.textContent, '');
        assert.equal(h.retry.hidden, true);
        assert.equal(h.input.files[0], selected);
        assert.notEqual(h.input.value, '');
        if (phase !== 'fetch') assertDisposed(h.loaders[0]);
        assert.deepEqual(h.revoked, h.created.map(({ url }) => url));
        h.window.dispatch('pageshow', { persisted: false });
        assert.equal(h.requests.length, 1);
        h.window.dispatch('pageshow', { persisted: true });
        assert.equal(h.requests.length, 2);
        assert.equal(h.requests[1].body.get('image'), selected);
        request.resolve(response());
        staleLoad?.();
        staleError?.();
        await tick();
        assertHidden(h);
        await h.finish();
        assert.equal(h.panel.hidden, false);
        h.window.dispatch('pagehide', { persisted: false });
        h.window.dispatch('pagehide', { persisted: false });
        assertHidden(h);
        assert.deepEqual(h.revoked, h.created.map(({ url }) => url));
        h.input.value = '';
        h.window.dispatch('pageshow', { persisted: true });
        assert.equal(h.requests.length, 2);
    });
}

test('initializing twice does not duplicate requests or lifecycle listeners', async t => {
    const h = setup(t);
    initProductImagePreviews();
    assert.equal(h.window.listeners.get('pagehide').length, 1);
    assert.equal(h.window.listeners.get('pageshow').length, 1);
    h.select();
    assert.equal(h.requests.length, 1);
    initProductImagePreviews();
    assert.equal(h.requests.length, 1);
    await h.finish();
    h.window.dispatch('pagehide');
    h.window.dispatch('pageshow', { persisted: true });
    assert.equal(h.requests.length, 2);
    await h.finish();
    h.clear.dispatch('click');
    assert.deepEqual(h.revoked, h.created.map(({ url }) => url));
});

test('canceled reset preserves file and preview', async t => {
    const h = setup(t);
    h.select();
    await h.finish();
    const event = { defaultPrevented: false };
    h.form.dispatch('reset', event);
    event.defaultPrevented = true;
    h.flushTimers();
    assert.equal(h.panel.hidden, false);
    assert.equal(h.input.files.length, 1);
    assert.deepEqual(h.revoked, []);
});
