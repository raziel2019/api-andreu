<x-admin.layout :save-form="$projects->isNotEmpty() ? 'cms-editor' : null" title="Projectes">
    <h1>Projectes del portafoli</h1>
    <p><a class="button" href="{{ route('admin.portfolio.projects.create') }}">Afegeix</a> <a href="{{ route('admin.portfolio.edit') }}">Presentació del portafoli</a></p>
    <form id="cms-editor" method="POST" action="{{ route('admin.portfolio.projects.order') }}">
    @csrf @method('PUT')
    <p>Un número més petit apareix abans. Els esborranys es conserven al CMS.</p>
    <div class="table-scroll"><table><thead><tr><th>Català</th><th>Castellano</th><th>Ordre</th><th>Acció</th></tr></thead><tbody>
    @forelse($projects as $index => $project)
        <tr><td>{{ $project->translated('title', 'ca') }} · {{ $project->status === 'published' ? 'Publicat' : 'Esborrany' }}</td><td lang="es">{{ $project->translated('title', 'es') }}</td><td><input type="hidden" name="items[{{ $index }}][id]" value="{{ $project->id }}"><x-admin.field :name="'items['.$index.'][sort_order]'" :label="'Ordre de '.$project->translated('title', 'ca')" type="number" :value="$project->sort_order" min="0" max="10000" :required="true" /><x-admin.error :name="'items.'.$index.'.id'" /></td><td><a href="{{ route('admin.portfolio.projects.edit', $project) }}">Edita</a></td></tr>
    @empty<tr><td colspan="4">Encara no hi ha contingut.</td></tr>@endforelse
    </tbody></table></div>
    <x-admin.error name="items" />
    @if($projects->isNotEmpty())<p><button type="submit">Desa l’ordre</button></p>@endif
    </form>
</x-admin.layout>
