<x-admin.layout save-form="cms-editor" title="Projectes">
    <h1>{{ $project->exists ? 'Edita' : 'Afegeix' }} · Projectes</h1>
    <p><a href="{{ route('admin.portfolio.projects.index') }}">Torna</a></p>
    @if($project->exists)
        <p>Previsualitza el contingut desat: <a href="{{ route('admin.portfolio.projects.preview', ['project' => $project, 'locale' => 'ca']) }}">Català</a> · <a href="{{ route('admin.portfolio.projects.preview', ['project' => $project, 'locale' => 'es']) }}">Castellano</a></p>
    @endif
    <form id="cms-editor" method="POST" enctype="multipart/form-data" action="{{ $project->exists ? route('admin.portfolio.projects.update', $project) : route('admin.portfolio.projects.store') }}">
        @csrf @if($project->exists) @method('PUT') @endif
        <x-admin.translated-field name="title" label="Títol" :value="$project->title" />
        <x-admin.field name="slug" label="Slug (URL, minúscules i guions)" :value="$project->slug" :required="true" />
        <x-admin.field name="sort_order" label="Ordre" type="number" :value="$project->sort_order" min="0" max="10000" :required="true" />
        <x-admin.translated-field name="description" label="Descripció" :value="$project->description" :multiline="true" :rich="true" :required="false" />
        <x-admin.translated-field name="body" label="Cos del projecte (HTML)" :value="$project->body" :multiline="true" :rich="true" :required="false" />
        <p><small>Etiquetes admeses: p, br, strong, em, u, h2, h3, ul, ol, li, blockquote, a. Els scripts, estils, incrustacions i atributs no admesos s’eliminen en desar. Les imatges es gestionen amb la biblioteca.</small></p>
        <label for="category">Categoria (opcional)</label>
        <select id="category" name="portfolio_category_id"><option value="">Sense categoria</option>
        @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('portfolio_category_id', $project->portfolio_category_id) === (string) $category->id)>{{ $category->translated('title', 'ca') }}</option>@endforeach
        </select><x-admin.error name="portfolio_category_id" />
        <x-admin.image-field name="featured_image" label="Imatge destacada" :path="$project->featured_image_path" />
        <x-admin.translated-field name="featured_image_alt" label="Text alternatiu de la imatge" :value="$project->featured_image_alt" :required="false" />
        <x-admin.field name="year" label="Any (opcional)" type="number" :value="$project->year" min="1000" max="9999" />
        <x-admin.field name="client" label="Client (opcional)" :value="$project->client" />
        <label for="status">Estat</label>
        <select id="status" name="status"><option value="draft" @selected(old('status', $project->status) === 'draft')>Esborrany</option><option value="published" @selected(old('status', $project->status) === 'published')>Publicat</option></select><x-admin.error name="status" />
        @php($gallery = old('gallery', $project->gallery ?? []))
        <section data-repeatable>
            <h2>Galeria d’imatges (opcional)</h2><p>Tria imatges de la biblioteca o puja’n de noves. Mou-les per ordenar-les.</p>
            <input type="hidden" name="gallery" value=""><x-admin.error name="gallery" />
            <div data-rows>@foreach(is_array($gallery) ? $gallery : [] as $index => $item)
                @include('admin.portfolio-gallery-row', ['index' => $index, 'item' => $item])
            @endforeach</div>
            <button type="button" data-add>Afegeix una imatge</button>
            <template data-template>@include('admin.portfolio-gallery-row', ['index' => '__INDEX__', 'item' => []])</template>
        </section>
        @pushOnce('scripts')<script src="{{ asset('admin-repeatables.js') }}" defer></script>@endPushOnce

        <p><button type="submit">Desa</button></p>
    </form>
    @if($project->exists)
    <form method="POST" action="{{ route('admin.portfolio.projects.destroy', $project) }}">
        @csrf @method('DELETE')
        <p>Eliminar el projecte conserva els fitxers a la biblioteca.</p>
        <label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo l’eliminació.</label><x-admin.error name="confirm_delete" />
        <button type="submit" class="danger">Elimina</button>
    </form>
    @endif
</x-admin.layout>
