<x-admin.layout title="Vista general" content-lang="es">
    <x-admin.page-heading section="Vista general" title="Un lugar para cuidar tu web." subtitle="Actualiza tus contenidos, afina los detalles y mantén viva tu historia." />
    <x-admin.status-banner />
    <div class="stats">
        <x-admin.stat-card :value="count($pages)" label="Páginas independientes" icon="pages" />
        <x-admin.stat-card :value="$languageCount" label="Idiomas disponibles" icon="globe" />
        <x-admin.stat-card :value="count(array_filter($pages))" label="Páginas visibles" icon="check" />
    </div>
    <div class="dashboard-columns">
        <section>
            <div class="section-heading"><h2>Las páginas de tu web</h2><small>{{ count($pages) }} páginas</small></div>
            <x-admin.page-list :pages="$pages" />
            <p><a href="{{ route('admin.pages.visibility.index') }}">Gestionar visibilidad</a></p>
        </section>
        <section>
            <div class="section-heading"><h2>Vista de tu web</h2>@if(config('app.frontend_url'))<a class="page-edit" href="{{ config('app.frontend_url') }}" target="_blank" rel="noopener noreferrer">Abrir web <x-admin.icon name="open" /></a>@endif</div>
            <x-admin.card class="preview-card">
                <div class="preview-bar"><span class="preview-dot"></span><span class="preview-dot"></span><span>Vista previa · contenido guardado</span></div>
                @if(config('app.frontend_url'))
                    <iframe src="{{ config('app.frontend_url') }}" title="Vista previa de la web pública" loading="lazy" sandbox="allow-scripts allow-same-origin" referrerpolicy="no-referrer"></iframe>
                @else
                    <div class="preview-empty">La dirección de la web pública está pendiente de configurar.</div>
                @endif
                <footer><p>Guarda tus cambios para publicarlos. Si la web no permite mostrarse aquí, usa «Abrir web».</p></footer>
            </x-admin.card>
            <x-admin.card class="spaced-card"><h2>Servicios del estudio</h2><p class="muted">{{ $serviceCount }} servicios en el CMS.</p><a href="{{ route('admin.services.index') }}">Gestionar servicios</a></x-admin.card>
        </section>
    </div>
    <x-admin.content-actions />
</x-admin.layout>
