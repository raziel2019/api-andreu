<x-admin.layout title="Accés">
    <section class="panel login">
        <h1>Accés a l’administració</h1>
        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <label for="email">Correu electrònic</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
            <label for="password">Contrasenya</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <p><button type="submit">Entra</button></p>
        </form>
    </section>
</x-admin.layout>
