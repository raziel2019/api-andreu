@props(['title'])
<!doctype html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Administració</title>
    <style>
        :root{color-scheme:light;--ink:#193c35;--line:#d2ded7}*{box-sizing:border-box}body{margin:0;background:#f5f4ef;color:#26392f;font:16px/1.5 system-ui,sans-serif}header{background:var(--ink);color:white;padding:1.2rem 4vw;display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap}header a{color:white}header form{margin-left:auto}nav{display:flex;gap:.8rem;flex-wrap:wrap}nav a{padding:.3rem .5rem}a{color:#205b48}main{max-width:1150px;margin:auto;padding:2rem 4vw}h1{margin-top:0}h2{font-size:1.3rem}fieldset,.panel{border:1px solid var(--line);border-radius:8px;background:white;padding:1.4rem;margin:1.3rem 0}legend{font-weight:700;padding:0 .5rem}.languages,.cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.2rem}.cards{grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}label{display:block;font-weight:600;margin:.8rem 0 .3rem}input,textarea{width:100%;padding:.7rem;border:1px solid #829c8b;border-radius:4px;font:inherit}textarea{min-height:110px;resize:vertical}input[type=checkbox]{width:auto}button,.button{display:inline-block;border:0;border-radius:4px;background:#205b48;color:white;padding:.65rem 1rem;font:inherit;cursor:pointer;text-decoration:none}button:focus-visible,a:focus-visible,input:focus-visible,textarea:focus-visible{outline:3px solid #d9a440;outline-offset:3px}.notice{background:#e3efe5;padding:1rem;border-left:4px solid #205b48}.errors{background:#fbe7e4;border-left:4px solid #a83222;padding:1rem}small,.muted{color:#526a5b}.image-preview{max-width:230px;max-height:180px;object-fit:contain}table{width:100%;border-collapse:collapse}td,th{text-align:left;vertical-align:top;padding:.75rem;border-bottom:1px solid var(--line);white-space:pre-line;overflow-wrap:anywhere}.summary-header{display:flex;align-items:center;justify-content:space-between;gap:1rem}.danger{background:#853d31}.field-error{color:#9c3025;font-weight:600;margin:.3rem 0}.row-controls{display:flex;gap:.5rem;flex-wrap:wrap}.repeatable{margin-block:2rem}[aria-invalid=true]{border-color:#9c3025}.login{max-width:500px;margin:3rem auto}@media(max-width:700px){.languages{grid-template-columns:1fr}header{align-items:flex-start}header form{margin-left:0}main{padding:1.5rem}table{font-size:.85rem}td,th{padding:.4rem}}
    </style>
</head>
<body>
<header>
    <strong>Andreu Gurrera · CMS</strong>
    @auth
        <nav aria-label="Seccions del CMS">
            <a href="{{ route('admin.home.edit') }}">Inicio</a>
            <a href="{{ route('admin.about.edit') }}">Sobre mi</a>
            <a href="{{ route('admin.services.index') }}">Serveis</a>
            <a href="{{ route('admin.portfolio.edit') }}">Portafoli</a>
            <a href="{{ route('admin.contact.edit') }}">Contacte</a>
            <a href="{{ route('admin.legal.edit', 'legal-notice') }}">Avís legal</a>
            <a href="{{ route('admin.legal.edit', 'privacy') }}">Privacitat</a>
            <a href="{{ route('admin.legal.edit', 'cookies') }}">Cookies</a>
            <a href="{{ route('admin.pages.visibility.index') }}">Visibilitat de pàgines</a>
            <a href="{{ route('admin.settings.edit') }}">Configuració</a>
        </nav>
        <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Surt</button></form>
    @endauth
</header>
<main>
    @if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())
        <div class="errors" role="alert"><strong>Revisa els camps del formulari.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    {{ $slot }}
</main>
@stack('scripts')
</body>
</html>
