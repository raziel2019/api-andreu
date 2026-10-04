<x-admin.layout title="Tauler">
    <h1>Tauler d’administració</h1>
    <p>Gestiona el contingut del web en català i castellà.</p>
    <div class="cards">
        <section class="panel"><h2>Inicio</h2><p>Edita textos, imatges, resums de serveis i crides a l’acció.</p><a class="button" href="{{ route('admin.home.edit') }}">Edita l’inici</a></section>
        <section class="panel"><h2>Configuració</h2><p>Actualitza les dades de contacte i els enllaços socials.</p><a class="button" href="{{ route('admin.settings.edit') }}">Edita la configuració</a></section>
        <section class="panel"><h2>Serveis</h2><p>{{ $serviceCount }} serveis al CMS.</p><a href="{{ route('admin.services.index') }}">Gestiona els serveis</a></section>
    </div>
    <p class="muted">Totes les seccions del web es poden editar des del menú superior. El català és el contingut original.</p>
</x-admin.layout>
