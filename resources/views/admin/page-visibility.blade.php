<x-admin.layout title="Visibilitat de pàgines">
    <h1>Visibilitat de pàgines</h1>
    <p>Ocultar una pàgina conserva el contingut. Per publicar una pàgina legal, completa primer el text en català i castellà al seu editor.</p>
    <section class="panel">
        <table>
            <thead><tr><th scope="col">Pàgina</th><th scope="col">Estat</th><th scope="col">Acció</th></tr></thead>
            <tbody>
                @foreach($labels as $key => $label)
                    <tr>
                        <th scope="row">{{ $label }}</th>
                        <td><strong class="{{ $pages[$key] ? 'notice' : 'muted' }}">{{ $pages[$key] ? 'Visible' : 'Oculta' }}</strong></td>
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
        </table>
    </section>
</x-admin.layout>
