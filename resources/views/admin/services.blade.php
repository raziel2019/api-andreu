<x-admin.layout :save-form="$services->isNotEmpty() ? 'cms-editor' : null" title="Serveis">
    <h1>Serveis</h1>
    <p><a class="button" href="{{ route('admin.services.create') }}">Afegeix un servei</a> <a href="{{ route('admin.services.page.edit') }}">Edita la presentació de la pàgina</a></p>
    <form id="cms-editor" method="POST" action="{{ route('admin.services.order') }}">
        @csrf @method('PUT')
        <section class="panel">
            <p>Un número més petit apareix abans. Els serveis no visibles es conserven al CMS.</p>
            <div class="table-scroll"><table><thead><tr><th>Català</th><th>Castellano</th><th>Estat</th><th>Ordre</th><th>Acció</th></tr></thead><tbody>
                @forelse($services as $index => $service)
                    <tr>
                        <td>{{ $service->translated('title', 'ca') }}</td><td lang="es">{{ $service->translated('title', 'es') }}</td>
                        <td>{{ $service->active ? 'Visible' : 'No visible' }}</td>
                        <td><input type="hidden" name="items[{{ $index }}][id]" value="{{ $service->id }}"><x-admin.field :name="'items['.$index.'][sort_order]'" :label="'Ordre de '.$service->translated('title', 'ca')" type="number" :value="$service->sort_order" min="0" max="10000" :required="true" /><x-admin.error :name="'items.'.$index.'.id'" /></td>
                        <td><a href="{{ route('admin.services.edit', $service) }}">Edita</a></td>
                    </tr>
                @empty<tr><td colspan="5">Encara no hi ha serveis. Afegeix-ne un.</td></tr>@endforelse
            </tbody></table></div>
            <x-admin.error name="items" />
            @if($services->isNotEmpty())<p><button type="submit">Desa l’ordre</button></p>@endif
        </section>
    </form>
</x-admin.layout>
