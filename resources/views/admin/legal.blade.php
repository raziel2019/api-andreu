<x-admin.layout save-form="cms-editor" title="Legal">
    <h1>{{ $page->translated('title', 'ca') }}</h1>
    <p>Esborrany fins que el client confirmi les dades i revisi el text legal. Activar publica immediatament les dues llengües.</p>
    <p>Text pla: separa els paràgrafs amb línies en blanc. No s’admet HTML.</p>
    <p><a href="{{ route('admin.settings.edit') }}">Revisa les dades legals i de contacte a Configuració</a>. No s’insereixen automàticament al text.</p>
    <form id="cms-editor" method="POST" action="{{ route('admin.legal.update', $section) }}">
        @csrf @method('PUT')
        <x-admin.translated-field name="title" label="Títol" :value="$page->title" />
        <x-admin.translated-field name="body" label="Contingut legal" :value="$page->body" :multiline="true" :required="false" />
        <x-admin.active-field :value="$page->active" />
        <p>Per publicar, cal contingut en català i castellà i revisió legal prèvia.</p>
        <button type="submit">Desa el contingut legal</button>
    </form>
</x-admin.layout>
