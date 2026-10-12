<x-admin.layout title="Categories">
    <h1>Categories del portafoli</h1>
    <p><a class="button" href="{{ route('admin.portfolio.categories.create') }}">Afegeix</a> <a href="{{ route('admin.portfolio.edit') }}">Presentació del portafoli</a></p>
    <div class="table-scroll"><table><thead><tr><th>Català</th><th>Castellano</th><th>Ordre</th><th>Acció</th></tr></thead><tbody>
    @forelse($categories as $category)
        <tr><td>{{ $category->translated('title', 'ca') }}</td><td lang="es">{{ $category->translated('title', 'es') }}</td><td>{{ $category->sort_order }}</td><td><a href="{{ route('admin.portfolio.categories.edit', $category) }}">Edita</a></td></tr>
    @empty<tr><td colspan="4">Encara no hi ha contingut.</td></tr>@endforelse
    </tbody></table></div>
</x-admin.layout>
