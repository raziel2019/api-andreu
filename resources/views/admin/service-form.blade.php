<x-admin.layout save-form="cms-editor" :title="$service->exists ? 'Edita el servei' : 'Nou servei'">
    <h1>{{ $service->exists ? 'Edita el servei' : 'Nou servei' }}</h1>
    <p><a href="{{ route('admin.services.index') }}">Torna als serveis</a></p>
    <form id="cms-editor" method="POST" action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" enctype="multipart/form-data">
        @csrf @if($service->exists) @method('PUT') @endif
        <x-admin.translated-field name="title" label="Títol" :value="$service->title" />
        <x-admin.translated-field name="subtitle" label="Subtítol" :value="$service->subtitle" :required="false" />
        <x-admin.translated-field name="description" label="Descripció" :value="$service->description" :multiline="true" />
        <x-admin.image-field name="icon" label="Icona o imatge del servei" :path="$service->icon_path" />
        <p><small>La icona és decorativa; el títol identifica el servei.</small></p>
        <x-admin.active-field :value="$service->active" />
        <x-admin.field name="sort_order" label="Ordre" type="number" :value="$service->sort_order" min="0" max="10000" :required="true" />
        <x-admin.repeatable name="features" label="Característiques del servei" :items="$service->exists ? $service->features->toArray() : []" />
        <p><button type="submit">Desa el servei</button></p>
    </form>
    @if($service->exists)
        <section class="panel">
            <h2>Elimina el servei</h2>
            <p>Si només el vols ocultar, desmarca «Visible al web». Eliminar-lo també elimina les seves característiques.</p>
            <form method="POST" action="{{ route('admin.services.destroy', $service) }}">
                @csrf @method('DELETE')
                <label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo que vull eliminar el servei i les seves característiques.</label>
                <x-admin.error name="confirm_delete" />
                <button type="submit" class="danger">Elimina definitivament</button>
            </form>
        </section>
    @endif
</x-admin.layout>
