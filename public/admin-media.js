(() => {
    const urls = new WeakMap();
    document.addEventListener('change', event => {
        if (event.target.matches('[data-summary-icon]') && event.target.value) {
            const summaryPicker = event.target.closest('[data-summary]').querySelector('[data-media-picker]');
            summaryPicker.querySelector('[data-media-select]').value = '';
            summaryPicker.querySelector('[data-media-upload]').value = '';
            summaryPicker.querySelector('[data-media-remove]').checked = false;
        }
        const picker = event.target.closest('[data-media-picker]');
        if (!picker) return;
        const preview = picker.querySelector('[data-media-preview]');
        const select = picker.querySelector('[data-media-select]');
        const upload = picker.querySelector('[data-media-upload]');
        const remove = picker.querySelector('[data-media-remove]');
        if (urls.has(picker)) { URL.revokeObjectURL(urls.get(picker)); urls.delete(picker); }
        if (event.target === select) { upload.value = ''; remove.checked = false; }
        if (event.target === upload) remove.checked = false;
        let url = select.selectedOptions[0]?.dataset.url || picker.dataset.currentUrl;
        if (upload.files[0] && ['image/png', 'image/jpeg', 'image/webp'].includes(upload.files[0].type)) {
            url = URL.createObjectURL(upload.files[0]); urls.set(picker, url);
        }
        if (url) preview.src = url;
        else preview.removeAttribute('src');
        preview.hidden = remove.checked || !url;
        const summary = picker.closest('[data-summary]');
        if (summary && (event.target === select || event.target === upload || remove.checked)) {
            summary.querySelector('[data-summary-icon]').value = '';
            summary.querySelectorAll('[data-summary-icon-preview]').forEach(image => image.hidden = true);
        }
    });
})();
