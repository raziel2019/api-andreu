@props(['id', 'name', 'value' => '', 'required' => false, 'errorKey'])
<div class="rich-text" data-rich-text data-required="{{ $required ? 'true' : 'false' }}">
    <div class="rich-toolbar" role="group" aria-label="Format del text" hidden>
        @foreach(['bold' => 'Negreta', 'italic' => 'Cursiva', 'underline' => 'Subratllat', 'paragraph' => 'Paràgraf', 'h2' => 'H2', 'h3' => 'H3', 'bulletList' => 'Llista', 'orderedList' => 'Llista numerada', 'blockquote' => 'Citació', 'link' => 'Enllaç', 'unlink' => 'Treu l’enllaç', 'undo' => 'Desfés', 'redo' => 'Refés', 'clear' => 'Neteja el format'] as $command => $label)
            <button type="button" data-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}">{{ $label }}</button>
        @endforeach
        <button type="button" data-source-toggle aria-pressed="false">HTML</button>
    </div>
    <div data-visual hidden></div>
    <textarea id="{{ $id }}" name="{{ $name }}" data-source @required($required) aria-label="Codi HTML" aria-invalid="{{ $errors->has($errorKey) ? 'true' : 'false' }}" aria-describedby="{{ $id }}-help{{ $errors->has($errorKey) ? ' error-'.str_replace('.', '-', $errorKey) : '' }}" data-initial-html="{{ app(\App\Services\RichText::class)->html($value) }}">{{ $value }}</textarea>
    <p id="{{ $id }}-help" class="rich-help">Text amb paràgrafs, H2/H3, negreta, cursiva, subratllat, llistes, citacions i enllaços. Ctrl/⌘ B, I, U; Ctrl/⌘ Z per desfer.</p>
    <p data-rich-status role="status" aria-live="polite"></p>
    <dialog data-link-dialog aria-labelledby="{{ $id }}-link-title">
        <h3 id="{{ $id }}-link-title">Edita l’enllaç</h3>
        <label for="{{ $id }}-url">URL (HTTP, HTTPS, mailto o ruta relativa)</label>
        <input id="{{ $id }}-url" data-link-url type="text" autocomplete="off">
        <p data-link-error role="alert"></p>
        <div class="rich-dialog-actions">
            <button type="button" data-link-save>Aplica</button>
            <button type="button" data-link-remove>Treu l’enllaç</button>
            <button type="button" data-link-cancel>Cancel·la</button>
        </div>
    </dialog>
</div>
@pushOnce('scripts', 'rich-text-assets')
    @vite(['resources/js/admin-rich-text.js'])
@endPushOnce
