<x-admin.layout :title="$option->exists ? 'Edita l’opció' : 'Nova opció'">
    <h1>{{ $option->exists ? 'Edita l’opció' : 'Nova opció' }}</h1>
    <p><a href="{{ route('admin.contact.edit') }}">Torna a Contacte</a></p>
    <form method="POST" action="{{ $option->exists ? route('admin.contact.options.update', $option) : route('admin.contact.options.store') }}">
        @csrf @if($option->exists) @method('PUT') @endif
        <x-admin.translated-field name="label" label="Text de l’opció" :value="$option->label" />
        <x-admin.active-field :value="$option->active" />
        <x-admin.field name="sort_order" label="Ordre" type="number" :value="$option->sort_order" min="0" max="10000" :required="true" />
        <p><button type="submit">Desa l’opció</button></p>
    </form>
    @if($option->exists)
        <section class="panel"><h2>Elimina l’opció</h2><p>Per conservar-la sense mostrar-la, desmarca «Visible al web».</p>
            <form method="POST" action="{{ route('admin.contact.options.destroy', $option) }}">
                @csrf @method('DELETE')
                <label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo que vull eliminar aquesta opció.</label>
                <x-admin.error name="confirm_delete" /><button type="submit" class="danger">Elimina definitivament</button>
            </form>
        </section>
    @endif
</x-admin.layout>
