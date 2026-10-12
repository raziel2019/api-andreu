@props(['title', 'saveForm' => null, 'contentLang' => 'ca'])
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Administración</title>
    <link rel="stylesheet" href="{{ asset('admin.css') }}">
    <script src="{{ asset('admin-shell.js') }}" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Ir al contenido</a>
@auth
    <div class="admin-shell">
        <x-admin.sidebar />
        <div class="admin-workspace">
            <x-admin.topbar :save-form="$saveForm" />
            <main id="main-content" class="admin-main" lang="{{ $contentLang }}" tabindex="-1">
                @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
                @if($errors->any())
                    <div class="errors" role="alert"><strong>Revisa els camps del formulari.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
@else
    <header class="login-header"><x-admin.icon name="brand" /><strong>Andreu Gurrera · CMS</strong></header>
    <main id="main-content" class="login-main" lang="ca" tabindex="-1">
        @if($errors->any())<div class="errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot }}
    </main>
@endauth
@stack('scripts')
</body>
</html>
