@props(['saveForm' => null])
<header class="admin-topbar">
    <div class="topbar-title">
        <button class="menu-toggle secondary" type="button" aria-controls="admin-sidebar" aria-expanded="false" data-sidebar-toggle>Menú</button>
        <div><p>Panel de contenidos</p><small>Tu archivo digital, a tu manera.</small></div>
    </div>
    <div class="topbar-actions">
        <span class="save-status" role="status" data-save-status>{{ $saveForm ? 'Guardado manual' : 'CMS conectado' }}</span>
        @if(config('app.frontend_url'))<a class="button secondary" href="{{ config('app.frontend_url') }}" target="_blank" rel="noopener noreferrer">Ver web <x-admin.icon name="external" /></a>@endif
        <x-admin.save-button :form="$saveForm" />
    </div>
</header>
