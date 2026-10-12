@props(['pages'])
@php
    $editors = [
        'home' => ['Inicio', 'Tu presentación y primera impresión', 'admin.home.edit', null],
        'about' => ['Sobre mí', 'Tu historia y manera de trabajar', 'admin.about.edit', null],
        'services' => ['Servicios', 'Lo que ofreces y cómo puedes ayudar', 'admin.services.page.edit', null],
        'portfolio' => ['Portfolio', 'Tu espacio de proyectos y patrimonio documental', 'admin.portfolio.edit', null],
        'contact' => ['Contacto', 'Una puerta abierta a nuevos proyectos', 'admin.contact.edit', null],
        'legal_notice' => ['Aviso legal', 'Información legal del sitio', 'admin.legal.edit', 'legal-notice'],
        'privacy' => ['Privacidad', 'Tratamiento de datos personales', 'admin.legal.edit', 'privacy'],
        'cookies' => ['Cookies', 'Información sobre cookies', 'admin.legal.edit', 'cookies'],
    ];
@endphp
<x-admin.card class="page-list">
    @foreach($pages as $key => $visible)
        @php([$label, $description, $route, $parameter] = $editors[$key])
        <div class="page-row">
            <span class="page-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <div class="page-details"><h3>{{ $label }}</h3><p>{{ $description }}</p><span @class(['visibility-badge', 'visible' => $visible])>{{ $visible ? 'Visible en la web' : 'Oculta en la web' }}</span></div>
            <a class="page-edit" href="{{ route($route, $parameter ? [$parameter] : []) }}" aria-label="Editar {{ $label }}">Editar</a>
        </div>
    @endforeach
</x-admin.card>
