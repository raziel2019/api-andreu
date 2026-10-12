import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { sanitizeHtml, safeLink } from './rich-text-policy';
import '../css/admin-rich-text.css';

const instances = new Map();

export function mountRichText(root) {
    if (instances.has(root)) return;
    const source = root.querySelector('[data-source]');
    const visual = root.querySelector('[data-visual]');
    const toolbar = root.querySelector('.rich-toolbar');
    const toggle = root.querySelector('[data-source-toggle]');
    const status = root.querySelector('[data-rich-status]');
    const dialog = root.querySelector('[data-link-dialog]');
    const url = root.querySelector('[data-link-url]');
    const error = root.querySelector('[data-link-error]');
    let htmlMode = false;
    let returnFocus;
    let changed = false;
    // Keep the original plain-text value until an actual edit, including legacy line endings.
    const initialValue = source.value;
    const editor = new Editor({
        element: visual,
        extensions: [StarterKit.configure({
            heading: { levels: [2, 3] }, trailingNode: false, code: false, codeBlock: false, strike: false, horizontalRule: false,
            link: { openOnClick: false, autolink: false, linkOnPaste: false, HTMLAttributes: { target: null, rel: null, class: null }, isAllowedUri: safeLink },
            orderedList: { HTMLAttributes: {} },
        })],
        content: sanitizeHtml(source.dataset.initialHtml || ''),
        editorProps: {
            attributes: {
                id: source.id + '-visual', role: 'textbox', 'aria-multiline': 'true',
                'aria-label': source.closest('[lang]')?.querySelector('label')?.textContent || 'Text',
                'aria-invalid': source.getAttribute('aria-invalid'), 'aria-describedby': source.getAttribute('aria-describedby'),
            },
            transformPastedHTML: html => {
                const clean = sanitizeHtml(html);
                status.textContent = 'Contingut enganxat normalitzat al format admès.';
                return clean;
            },
        },
        onUpdate: () => { changed = true; sync(); },
        onTransaction: () => updateToolbar(),
    });
    const initialHtml = sanitizeHtml(editor.getHTML());
    function content() { return editor.isEmpty ? '' : sanitizeHtml(editor.getHTML()); }
    function sync() {
        source.value = changed && content() !== initialHtml ? content() : initialValue;
        source.dispatchEvent(new Event('input', { bubbles: true }));
    }
    function updateToolbar() {
        if (!instances.has(root)) return;
        root.querySelectorAll('[data-command]').forEach(button => {
            const command = button.dataset.command;
            const state = command === 'h2' || command === 'h3' ? editor.isActive('heading', { level: Number(command[1]) }) : editor.isActive(command);
            if (['bold', 'italic', 'underline', 'paragraph', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'link'].includes(command)) {
                button.setAttribute('aria-pressed', String(state));
            }
            button.disabled = htmlMode || (command === 'undo' && !editor.can().undo()) || (command === 'redo' && !editor.can().redo());
        });
    }
    function applySource() {
        const before = source.value;
        const clean = sanitizeHtml(before);
        editor.commands.setContent(clean, { emitUpdate: false });
        const normalized = content();
        status.textContent = before !== normalized ? 'HTML normalitzat: només es conserva el format admès i els enllaços segurs.' : '';
        changed = normalized !== initialHtml;
        source.value = changed ? normalized : initialValue;
    }
    function switchMode() {
        if (htmlMode) {
            applySource(); htmlMode = false; source.hidden = true; visual.hidden = false;
            editor.commands.focus();
        } else {
            source.value = content(); htmlMode = true; visual.hidden = true; source.hidden = false; source.focus();
        }
        localeLabel?.setAttribute('for', htmlMode ? source.id : editor.view.dom.id);
        toggle.textContent = htmlMode ? 'Visual' : 'HTML';
        toggle.setAttribute('aria-pressed', String(htmlMode));
        updateToolbar();
    }
    function openLink(button) {
        returnFocus = button;
        error.textContent = '';
        url.value = editor.getAttributes('link').href || '';
        dialog.showModal(); url.focus(); url.select();
    }
    function closeLink() { dialog.close(); returnFocus?.focus(); }
    function saveLink() {
        const href = url.value.trim();
        if (href && !safeLink(href)) { error.textContent = 'Indica un enllaç HTTP, HTTPS, mailto o una ruta relativa segura.'; url.focus(); return; }
        const chain = editor.chain().focus().extendMarkRange('link');
        if (href) chain.setLink({ href }).run(); else chain.unsetLink().run();
        closeLink();
    }
    const commands = {
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        underline: () => editor.chain().focus().toggleUnderline().run(),
        paragraph: () => editor.chain().focus().setParagraph().run(),
        h2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        h3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        blockquote: () => editor.chain().focus().toggleBlockquote().run(),
        unlink: () => editor.chain().focus().extendMarkRange('link').unsetLink().run(),
        undo: () => editor.chain().focus().undo().run(), redo: () => editor.chain().focus().redo().run(),
        clear: () => editor.chain().focus().unsetAllMarks().clearNodes().run(),
    };
    toolbar.addEventListener('mousedown', event => { if (event.target.closest('[data-command]')) event.preventDefault(); });
    toolbar.addEventListener('click', event => {
        const button = event.target.closest('[data-command]');
        if (!button || htmlMode) return;
        if (button.dataset.command === 'link') openLink(button); else commands[button.dataset.command]?.();
    });
    toggle.addEventListener('click', switchMode);
    root.querySelector('[data-link-save]').addEventListener('click', saveLink);
    root.querySelector('[data-link-remove]').addEventListener('click', () => { url.value = ''; saveLink(); });
    root.querySelector('[data-link-cancel]').addEventListener('click', closeLink);
    dialog.addEventListener('cancel', () => returnFocus?.focus());
    url.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); saveLink(); } });
    visual.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openLink(root.querySelector('[data-command="link"]')); }
    });
    source.required = false;
    source.hidden = true; visual.hidden = false; toolbar.hidden = false;
    const localeLabel = document.querySelector(`label[for="${CSS.escape(source.id)}"]`);
    localeLabel?.setAttribute('for', editor.view.dom.id);
    instances.set(root, { editor, submit: () => { if (htmlMode) applySource(); else sync(); }, valid: () => !root.dataset.required || root.dataset.required !== 'true' || !editor.isEmpty, focus: () => htmlMode ? source.focus() : editor.commands.focus() });
    updateToolbar();
}

function mount(container) {
    container.querySelectorAll('[data-rich-text]').forEach(mountRichText);
}
mount(document);
document.addEventListener('cms-row-added', event => {
    mount(event.target);
    event.target.querySelector('[contenteditable]')?.focus();
});
new MutationObserver(records => {
    for (const record of records) for (const node of record.addedNodes) {
        if (!(node instanceof Element)) continue;
        if (node.matches('[data-rich-text]')) mountRichText(node);
        mount(node);
    }
    for (const [root, instance] of instances) if (!root.isConnected) { instance.editor.destroy(); instances.delete(root); }
}).observe(document.body, { childList: true, subtree: true });
document.addEventListener('submit', event => {
    for (const [root, instance] of instances) {
        if (!event.target.contains(root)) continue;
        instance.submit();
        if (!instance.valid()) { event.preventDefault(); root.querySelector('[data-rich-status]').textContent = 'Aquest camp és obligatori.'; instance.focus(); break; }
    }
}, true);
