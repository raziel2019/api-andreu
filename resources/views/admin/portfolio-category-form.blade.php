<x-admin.layout save-form="cms-editor" title="Categories">
    <h1>{{ $category->exists ? 'Edita' : 'Afegeix' }} · Categories</h1>
    <p><a href="{{ route('admin.portfolio.categories.index') }}">Torna</a></p>
    <form id="cms-editor" method="POST" enctype="multipart/form-data" action="{{ $category->exists ? route('admin.portfolio.categories.update', $category) : route('admin.portfolio.categories.store') }}">
        @csrf @if($category->exists) @method('PUT') @endif
        <x-admin.translated-field name="title" label="Títol" :value="$category->title" />
        <x-admin.field name="slug" label="Slug (URL, minúscules i guions)" :value="$category->slug" :required="true" />
        <x-admin.field name="sort_order" label="Ordre" type="number" :value="$category->sort_order" min="0" max="10000" :required="true" />
        <p><button type="submit">Desa</button></p>
    </form>
    @if($category->exists)
    <form method="POST" action="{{ route('admin.portfolio.categories.destroy', $category) }}">
        @csrf @method('DELETE')
        <p>Eliminar la categoria conserva els projectes, sense categoria.</p>
        <label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo l’eliminació.</label><x-admin.error name="confirm_delete" />
        <button type="submit" class="danger">Elimina</button>
    </form>
    @endif
</x-admin.layout>
