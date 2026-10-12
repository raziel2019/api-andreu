<section aria-labelledby="options-heading">
    <h2 id="options-heading">Opcions de serveis del formulari</h2>
    <p><a class="button" href="{{ route('admin.contact.options.create') }}">Afegeix una opció</a></p>
    <form method="POST" action="{{ route('admin.contact.options.order') }}">
        @csrf @method('PUT')
        <div class="panel">
            <p>Un número més petit apareix abans. Una opció no visible no apareix al formulari públic.</p>
            <div class="table-scroll"><table><thead><tr><th>Català</th><th>Castellano</th><th>Estat</th><th>Ordre</th><th>Acció</th></tr></thead><tbody>
                @forelse($options as $index => $option)
                    <tr>
                        <td>{{ $option->translated('label', 'ca') }}</td><td lang="es">{{ $option->translated('label', 'es') }}</td>
                        <td>{{ $option->active ? 'Visible' : 'No visible' }}</td>
                        <td><input type="hidden" name="items[{{ $index }}][id]" value="{{ $option->id }}"><x-admin.field :name="'items['.$index.'][sort_order]'" :label="'Ordre de l’opció '.($index + 1)" type="number" :value="$option->sort_order" min="0" max="10000" :required="true" /><x-admin.error :name="'items.'.$index.'.id'" /></td>
                        <td><a href="{{ route('admin.contact.options.edit', $option) }}">Edita</a></td>
                    </tr>
                @empty<tr><td colspan="5">Encara no hi ha opcions. Afegeix-ne una.</td></tr>@endforelse
            </tbody></table></div>
            <x-admin.error name="items" />
            @if($options->isNotEmpty())<p><button type="submit">Desa l’ordre de les opcions</button></p>@endif
        </div>
    </form>
</section>
