export function initInventoryBulk() {
    const form = document.querySelector('[data-inventory-bulk]');
    if (!form) return;

    const checkboxes = [...form.querySelectorAll('[data-bulk-id]')];
    const selectAll = form.querySelector('[data-bulk-all]');
    const action = form.querySelector('[data-bulk-action]');
    const submit = form.querySelector('[data-bulk-submit]');
    const count = form.querySelector('[data-bulk-count]');
    const selectedCount = () => checkboxes.filter(checkbox => checkbox.checked).length;
    const update = () => {
        const selected = selectedCount();
        count.textContent = `Đã chọn ${selected} sản phẩm`;
        selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
        selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        selectAll.disabled = checkboxes.length === 0;
        submit.disabled = selected === 0 || !['active', 'inactive', 'delete'].includes(action.value);
    };

    selectAll.addEventListener('change', () => {
        checkboxes.forEach(checkbox => { checkbox.checked = selectAll.checked; });
        update();
    });
    checkboxes.forEach(checkbox => checkbox.addEventListener('change', update));
    action.addEventListener('change', update);
    form.addEventListener('submit', event => {
        update();
        if (submit.disabled) {
            event.preventDefault();
            return;
        }
        if (action.value === 'delete' && !window.confirm(
            `Bạn có chắc muốn xóa ${selectedCount()} sản phẩm đã chọn? Thao tác này không thể hoàn tác.`,
        )) {
            event.preventDefault();
        }
    });
    window.addEventListener('pageshow', update);
    update();
}
