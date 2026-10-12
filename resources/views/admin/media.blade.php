<x-admin.layout title="Biblioteca de mitjans">
    <x-admin.page-heading section="Biblioteca de mitjans" title="Biblioteca de mitjans" />
    <x-admin.status-banner />
    <p>Reutilitza imatges i icones en qualsevol camp. Treure una selecció conserva el fitxer; substituir-la no modifica altres pàgines.</p>
    <x-admin.card>
        <h2>Puja un fitxer a la biblioteca</h2>
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
            @csrf
            <label for="library-upload">PNG, JPEG o WebP · màxim 5 MB</label>
            <input id="library-upload" type="file" name="image" required accept="image/png,image/jpeg,image/webp">
            <x-admin.error name="image" />
            <x-admin.translated-field name="alt" label="Text alternatiu del fitxer" :required="false" />
            <button type="submit">Puja i desa a la biblioteca</button>
        </form>
    </x-admin.card>
    <h2>Fitxers disponibles</h2>
    <div class="media-grid">
        @foreach($assets as $asset)
            <x-admin.card class="media-card">
                <div class="media-preview"><a href="{{ $asset->url() }}" target="_blank" rel="noopener"><img src="{{ $asset->url() }}" alt="{{ $asset->alt['ca'] ?? $asset->original_filename }}" loading="lazy"></a></div>
                <div class="media-card-body"><h3>{{ $asset->original_filename }}</h3><p>#{{ $asset->id }} · {{ $asset->mime_type }} · {{ $asset->size }} bytes</p><p>{{ $asset->width ?? '—' }} × {{ $asset->height ?? '—' }}</p></div>
            </x-admin.card>
        @endforeach
    </div>
    {{ $assets->links() }}
    <h2>Assignacions del web</h2>
    <div class="media-grid">
        @foreach($slots as $key => $slot)
            @php($path = \App\Services\CmsMediaSlots::path($slot))
            <x-admin.card class="media-card">
                <div class="media-card-body">
                    <h3>{{ $slot['label'] }}</h3>
                    <span class="media-path">{{ $path ?: 'Sense fitxer' }}</span>
                    <form method="POST" action="{{ route('admin.media.update', $key) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <x-admin.media-picker name="image" :label="$slot['label']" :path="$path" />
                        <button type="submit">Desa l’assignació</button>
                    </form>
                </div>
            </x-admin.card>
        @endforeach
    </div>
    <x-admin.content-actions />
</x-admin.layout>
