@php
    $navigation = [
        ['admin.dashboard', 'Vista general', 'overview', request()->routeIs('admin.dashboard')],
        ['admin.content.index', 'Contenidos y traducciones', 'content', ! request()->routeIs('admin.dashboard', 'admin.media.*', 'admin.appearance.*', 'admin.settings.*')],
        ['admin.media.index', 'Biblioteca de imágenes', 'media', request()->routeIs('admin.media.*')],
        ['admin.appearance.index', 'Apariencia', 'appearance', request()->routeIs('admin.appearance.*')],
        ['admin.settings.edit', 'Identidad y contacto', 'identity', request()->routeIs('admin.settings.*')],
    ];
@endphp
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Administración">
    <div class="sidebar-heading">
        <a class="brand" href="{{ route('admin.dashboard') }}"><x-admin.icon name="brand" /><span>Andreu Gurrera</span></a>
        <button type="button" class="sidebar-close secondary" data-sidebar-close aria-label="Cerrar navegación">Cerrar</button>
    </div>
    <p class="brand-caption">Estudio · Administración</p>
    <p class="nav-caption">Tu espacio de edición</p>
    <nav class="admin-navigation" aria-label="Secciones del CMS">
        @foreach($navigation as [$route, $label, $icon, $active])
            @php($asset = $icon === 'overview' && ! $active ? 'overview-inactive' : (in_array($icon, ['media', 'appearance']) && $active ? $icon.'-active' : $icon))
            <a href="{{ route($route) }}" @class(['nav-item', 'is-active' => $active]) @if($active) aria-current="page" @endif>
                <x-admin.icon :name="$asset" /><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <section class="language-card" aria-label="Idiomas disponibles">
        <x-admin.icon name="languages" />
        <h2>Una web, dos idiomas</h2>
        <p>Cuida cada palabra en catalán y español.</p>
        <div class="locale-badges"><span>CA</span><span>ES</span></div>
    </section>
    @if(config('app.frontend_url'))
        <a class="return-link" href="{{ config('app.frontend_url') }}" target="_blank" rel="noopener noreferrer">Volver a la web <x-admin.icon name="return" /></a>
    @else
        <p class="muted">Web pública pendiente de configurar.</p>
    @endif
    <form class="logout-form" method="POST" action="{{ route('admin.logout') }}">@csrf<button class="text-button" type="submit">Cerrar sesión</button></form>
</aside>
<button class="sidebar-backdrop" type="button" data-sidebar-close aria-label="Cerrar navegación" hidden></button>
