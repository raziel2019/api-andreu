document.querySelectorAll('[data-repeatable]').forEach(section => {
    const rows = section.querySelector('[data-rows]');
    let nextIndex = Math.max(-1, ...Array.from(rows.children, row => Number(row.dataset.index) || 0)) + 1;
    const renumber = () => Array.from(rows.children).forEach((row, position) => { row.querySelector('[data-order]').value = position; });
    section.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        if (button.hasAttribute('data-add')) {
            if (rows.children.length >= 50) return;
            rows.insertAdjacentHTML('beforeend', section.querySelector('[data-template]').innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
            renumber();
            rows.lastElementChild.querySelector('textarea').focus();
            return;
        }
        const row = button.closest('[data-row]');
        if (!row) return;
        if (button.hasAttribute('data-remove')) row.remove();
        if (button.hasAttribute('data-up') && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
        if (button.hasAttribute('data-down') && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
        renumber();
    });
});
