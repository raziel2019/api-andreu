(() => {
    const form = document.querySelector('[data-theme-form]');
    if (!form) return;
    const preview = form.querySelector('[data-theme-preview]');
    const status = form.querySelector('[data-theme-preview-status]');
    const validHex = value => /^#[0-9a-f]{6}$/i.test(value);
    const fields = [...form.querySelectorAll('[data-theme-field]')];
    const setPreview = (name, value) => {
        if (validHex(value)) {
            preview.style.setProperty(name === 'primary_color' ? '--preview-primary' : '--preview-background', value.toUpperCase());
        }
    };
    setPreview('primary_color', preview.dataset.previewPrimary);
    setPreview('background_color', preview.dataset.previewBackground);
    const refreshStatus = () => {
        status.textContent = fields.every(field => validHex(field.querySelector('[data-theme-hex]').value))
            ? 'Vista previa de los valores del formulario. Guarda para conservarlos.'
            : 'Introduce un color válido (#RRGGBB). La vista previa conserva el último valor válido.';
    };
    fields.forEach(field => {
        const hex = field.querySelector('[data-theme-hex]');
        const picker = field.querySelector('[data-theme-picker]');
        const reset = field.querySelector('[data-theme-reset]');
        const syncHex = () => {
            if (validHex(hex.value)) {
                picker.value = hex.value;
                setPreview(hex.name, hex.value);
            }
            refreshStatus();
        };
        picker.disabled = false;
        reset.disabled = false;
        hex.addEventListener('input', syncHex);
        hex.addEventListener('change', () => {
            if (validHex(hex.value)) hex.value = hex.value.toUpperCase();
            syncHex();
        });
        picker.addEventListener('input', () => {
            hex.value = picker.value.toUpperCase();
            hex.dispatchEvent(new Event('input', {bubbles:true}));
        });
        reset.addEventListener('click', () => {
            if (hex.value.toUpperCase() === field.dataset.default) return;
            if (!window.confirm(`¿Restablecer este color a ${field.dataset.default}? El cambio se aplicará al guardar.`)) return;
            hex.value = field.dataset.default;
            hex.dispatchEvent(new Event('input', {bubbles:true}));
            hex.focus();
        });
        syncHex();
    });
})();
