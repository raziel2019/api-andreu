<x-admin.layout title="Visibilitat de pàgines">
    <x-admin.page-heading section="Contenidos y traducciones" title="Visibilitat de pàgines" subtitle="Activa o desactiva les pàgines sense perdre contingut." />
    <p>Ocultar una pàgina conserva el contingut. Per publicar una pàgina legal, completa primer el text en català i castellà al seu editor.</p>
    <section class="panel">
        <div class="table-scroll"><table>
            <thead><tr><th scope="col">Pàgina</th><th scope="col">Estat</th><th scope="col">Acció</th></tr></thead>
            <tbody>
                @foreach($labels as $key => $label)
                    <tr>
                        <th scope="row">{{ $label }}</th>
                        <td><strong class="visibility-badge {{ $pages[$key] ? 'visible' : '' }}">{{ $pages[$key] ? 'Visible' : 'Oculta' }}</strong></td>
                        <td>
                            <form method="POST" action="{{ route('admin.pages.visibility.update', $key) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="active" value="{{ $pages[$key] ? 0 : 1 }}">
                                <button type="submit" class="{{ $pages[$key] ? 'danger' : '' }}" aria-label="{{ ($pages[$key] ? 'Oculta ' : 'Mostra ').$label }}">{{ $pages[$key] ? 'Oculta' : 'Mostra' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>
</x-admin.layout>
