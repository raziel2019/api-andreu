<x-admin.layout :title="$title">
    <h1>{{ $title }}</h1>
    <p class="notice">Consulta del contingut actual. L’edició d’aquesta secció està pendent.</p>
    @foreach($records as $record)
        <section class="panel">
            <table>
                <thead><tr><th>Camp</th><th>Català</th><th>Castellano</th></tr></thead>
                <tbody>
                    @foreach($record->getAttributes() as $field => $raw)
                        @php($value = $record->getAttribute($field))
                        @if(is_array($value) && (array_key_exists('ca', $value) || array_key_exists('es', $value)))
                            <tr><th>{{ __('admin.fields.'.$field) }}</th><td>{{ $value['ca'] ?? '' }}</td><td lang="es">{{ $value['es'] ?? '' }}</td></tr>
                        @elseif(in_array($field, ['active', 'under_construction', 'sort_order', 'slug']))
                            <tr><th>{{ __('admin.fields.'.$field) }}</th><td colspan="2">{{ is_bool($value) ? ($value ? 'Sí' : 'No') : $value }}</td></tr>
                        @endif
                    @endforeach
                    @foreach($record->getRelations() as $relationName => $relation)
                        <tr><th colspan="3">{{ __('admin.relations.'.$relationName) }}</th></tr>
                        @foreach($relation as $item)
                            <tr><th>{{ $item->sort_order + 1 }}</th><td>{{ $item->translated('content', 'ca') ?? $item->translated('label', 'ca') }}</td><td lang="es">{{ $item->translated('content', 'es') ?? $item->translated('label', 'es') }}</td></tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
</x-admin.layout>
