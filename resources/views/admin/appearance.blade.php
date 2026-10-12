<x-admin.layout title="Apariencia" content-lang="es" save-form="cms-editor">
    <x-admin.page-heading section="Apariencia" title="Apariencia" subtitle="Edita, revisa y guarda cuando todo esté listo." />
    <x-admin.status-banner />
    <form id="cms-editor" method="POST" action="{{ route('admin.appearance.update') }}" data-theme-form>
        @csrf @method('PUT')
        <x-admin.card class="appearance-card">
            <h2>La personalidad de tu archivo</h2>
            <p>Elige tus colores y comprueba el resultado en la vista previa.</p>
            <p id="theme-format" class="muted">Usa seis dígitos hexadecimales (#RRGGBB). Los cambios se guardan al pulsar Guardar.</p>
            @foreach(['primary_color' => ['Color principal', \App\Models\SiteSetting::DEFAULT_PRIMARY_COLOR], 'background_color' => ['Fondo de la web', \App\Models\SiteSetting::DEFAULT_BACKGROUND_COLOR]] as $field => [$label, $default])
                <div class="color-row theme-field" data-theme-field data-default="{{ $default }}">
                    <div class="theme-hex-field">
                        <x-admin.field :name="$field" :label="$label" :value="$settings->$field" :required="true" maxlength="7" pattern="#[0-9A-Fa-f]{6}" spellcheck="false" autocomplete="off" data-theme-hex title="Formato: #RRGGBB" />
                    </div>
                    <div class="theme-picker-actions">
                        <label class="sr-only" for="picker-{{ $field }}">Selector de {{ $label }}</label>
                        <input id="picker-{{ $field }}" type="color" value="{{ $settings->$field }}" data-theme-picker disabled>
                        <button class="secondary theme-reset" type="button" data-theme-reset disabled>Restablecer {{ $label }}</button>
                    </div>
                </div>
            @endforeach
            <noscript><p>Introduce los valores hexadecimales para guardar. El selector, la vista previa y el restablecimiento requieren JavaScript.</p></noscript>
            <p class="appearance-note">Restablecer prepara el valor por defecto de ese campo. Guarda los cambios para confirmarlo en el CMS. La web pública aplicará estos colores cuando se conecte al tema del CMS.</p>
            <p><button type="submit"><x-admin.icon name="save" />Guardar cambios</button></p>
        </x-admin.card>
        <x-admin.card class="appearance-card spaced-card">
            <h2>Vista previa del tema</h2>
            <p class="muted">Ejemplo visual del color principal y el fondo. No modifica el contenido de tu web.</p>
            <div class="theme-preview" data-theme-preview data-preview-primary="{{ $settings->primary_color }}" data-preview-background="{{ $settings->background_color }}">
                <p class="theme-preview-title">Tu archivo digital</p>
                <p>Un espacio para cuidar tu historia.</p>
                <span class="theme-preview-button">Un nuevo proyecto</span>
            </div>
            <p class="muted" role="status" aria-live="polite" data-theme-preview-status></p>
        </x-admin.card>
    </form>
    <x-admin.content-actions />
    @push('scripts')
        <link rel="stylesheet" href="{{ asset('admin-theme.css') }}">
        <script src="{{ asset('admin-theme.js') }}" defer></script>
    @endpush
</x-admin.layout>
