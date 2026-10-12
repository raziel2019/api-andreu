<x-admin.layout title="Contenidos y traducciones" content-lang="es">
    <x-admin.page-heading section="Contenidos y traducciones" title="Cada palabra cuenta." subtitle="Edita el contenido original en catalán y su traducción al español." />
    <x-admin.status-banner />
    <div class="section-heading"><h2>Las páginas de tu web</h2><a class="button secondary" href="{{ route('admin.pages.visibility.index') }}">Gestionar visibilidad</a></div>
    <x-admin.page-list :pages="$pages" />
    <div class="cards">
        <x-admin.card class="spaced-card"><h2>Servicios</h2><p class="muted">Gestiona los servicios, sus traducciones y su orden.</p><a href="{{ route('admin.services.index') }}">Editar servicios</a></x-admin.card>
        <x-admin.card class="spaced-card"><h2>Contenido compartido</h2><p class="muted">Edita la llamada de contacto y el pie de página en CA/ES.</p><a href="{{ route('admin.settings.edit') }}">Editar contenido compartido</a></x-admin.card>
    </div>
</x-admin.layout>
