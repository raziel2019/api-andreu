import DOMPurify from 'dompurify';

export const tags = ['p', 'br', 'strong', 'em', 'u', 'h2', 'h3', 'ul', 'ol', 'li', 'blockquote', 'a'];

export function safeLink(value) {
    if (!value || /[\x00-\x20\x7f\\]/.test(value) || value.startsWith('//')) return false;
    if (/^https?:\/\//i.test(value)) {
        try { const url = new URL(value); return Boolean(url.hostname) && !url.username && !url.password; } catch { return false; }
    }
    if (/^mailto:[^\s@]+@[^\s@]+/i.test(value)) return true;
    return /^(?:\/(?!\/)|#|\?|\.?\.?\/|[a-z0-9_-]+(?:\/|\.|$))/i.test(value) && !/^[^/?#]*:/.test(value);
}

export function sanitizeHtml(value) {
    const canonical = value.replace(/<(\/?)b(?=[\s>])/gi, '<$1strong').replace(/<(\/?)i(?=[\s>])/gi, '<$1em');
    const fragment = DOMPurify.sanitize(canonical, {
        ALLOWED_TAGS: tags, ALLOWED_ATTR: ['href'], ALLOW_DATA_ATTR: false,
        ALLOW_ARIA_ATTR: false, RETURN_DOM_FRAGMENT: true,
        FORBID_TAGS: ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'textarea', 'button', 'img', 'video', 'audio', 'noscript'],
        FORBID_CONTENTS: ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'form', 'input', 'textarea', 'button', 'img', 'video', 'audio', 'noscript'],
    });
    fragment.querySelectorAll('*').forEach(node => {
        for (const attr of [...node.attributes]) {
            if (node.tagName !== 'A' || attr.name !== 'href' || !safeLink(attr.value)) node.removeAttribute(attr.name);
        }
    });
    const container = document.createElement('div');
    container.append(fragment);
    return container.innerHTML;
}
