(() => {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const narrow = window.matchMedia('(max-width: 1000px)');
    const workspace = document.querySelector('.admin-workspace');
    const syncDrawer = () => {
        const open = narrow.matches && document.body.classList.contains('sidebar-open');
        if (sidebar) {
            sidebar.inert = narrow.matches && !open;
            sidebar.setAttribute('aria-hidden', String(narrow.matches && !open));
        }
        if (workspace) workspace.inert = open;
    };
    const setOpen = (open) => {
        document.body.classList.toggle('sidebar-open', open);
        toggle?.setAttribute('aria-expanded', String(open));
        if (backdrop) backdrop.hidden = !open;
        syncDrawer();
        if (open) sidebar?.querySelector('[data-sidebar-close]')?.focus();
        else toggle?.focus();
    };
    syncDrawer();
    narrow.addEventListener('change', () => {
        if (!narrow.matches) {
            document.body.classList.remove('sidebar-open');
            toggle?.setAttribute('aria-expanded', 'false');
            if (backdrop) backdrop.hidden = true;
        }
        syncDrawer();
    });
    toggle?.addEventListener('click', () => setOpen(!document.body.classList.contains('sidebar-open')));
    document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', event => {
        if (!document.body.classList.contains('sidebar-open')) return;
        if (event.key === 'Escape') setOpen(false);
        if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a, button, input')].filter(element => element.getClientRects().length && !element.disabled);
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    const form = document.getElementById('cms-editor');
    const status = document.querySelector('[data-save-status]');
    const markDirty = () => { if (status) status.textContent = 'Cambios sin guardar'; };
    form?.addEventListener('input', markDirty);
    form?.addEventListener('change', markDirty);
})();
