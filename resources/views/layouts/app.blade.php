<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#123f32">
    <title>@yield('title', 'GanTek')</title>
    <link rel="icon" href="{{ asset('images/logo-gantek.png') }}">
    <link rel="stylesheet" href="{{ asset('css/gantek.css') }}?v={{ filemtime(public_path('css/gantek.css')) }}">
    <script defer src="{{ asset('js/gantek.js') }}?v={{ filemtime(public_path('js/gantek.js')) }}"></script>
    @stack('styles')
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<div class="app-shell">
    <aside class="sidebar" id="navigation" aria-label="Navegación principal">
        <a class="brand" href="{{ route('dashboard') }}"><img src="{{ asset('images/logo-gantek.png') }}" alt="GanTek, inicio"><span>Tu ganado, en buenas manos</span></a>
        <button type="button" class="nav-close" data-close-menu aria-label="Cerrar menú">Cerrar menú ×</button>
        <p class="nav-label">GESTIÓN DE TU FINCA</p>
        <nav>
            @foreach([
                ['dashboard', 'Inicio', 'dashboard'],
                ['fincas.index', 'Fincas', 'fincas.*'],
                ['lotes.index', 'Lotes', 'lotes.*'],
                ['ganado.index', 'Ganado', 'ganado.*'],
                ['veterinarios.index', 'Veterinarios', 'veterinarios.*'],
                ['vacunas.index', 'Catálogo de vacunas', 'vacunas.*'],
                ['vacunaciones.index', 'Vacunaciones', 'vacunaciones.*'],
                ['ordenios.index', 'Ordeños', 'ordenios.*'],
                ['produccion.index', 'Producción y reportes', 'produccion.*'],
            ] as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>@include('partials.icon', ['name' => $label]){{ $label }}</a>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            <p>Tecnología para un campo más productivo.</p>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-btn">Cerrar sesión ↗</button></form>
        </div>
    </aside>
    <button type="button" class="menu-backdrop" data-close-menu aria-label="Cerrar menú" tabindex="-1" hidden></button>
    <div class="workspace">
        <header class="topbar">
            <div><button type="button" class="menu-toggle" aria-controls="navigation" aria-expanded="false" aria-label="Abrir menú">☰</button><span class="topbar-label">GAN TEK <span> / </span> Tu espacio ganadero</span></div>
            <div class="topbar-actions"><a class="notice-link" href="{{ route('dashboard') }}#avisos">Avisos</a><span class="user-chip"><span class="avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span>{{ auth()->user()->name }}<small>{{ auth()->user()->puede_gestionar_catalogos ? 'Administración de catálogos' : 'Ganadero' }}</small></span></span></div>
        </header>
        <main class="content" id="contenido" tabindex="-1">
            <section class="page-heading">
                <div><p class="eyebrow">CUIDA · ORGANIZA · CRECE</p><h1>@yield('heading', 'GanTek')</h1><p>@yield('subheading', 'La información de tu finca, siempre a tu alcance.')</p></div>
                <span class="heading-date">{{ today()->format('d / m / Y') }}</span>
            </section>
            @foreach(['success' => 'Listo', 'error' => 'No se pudo completar', 'warning' => 'Ten en cuenta', 'info' => 'Información'] as $tone => $title)
                @if(session($tone))
                    <div class="alert {{ $tone }} flash-message" role="{{ $tone === 'error' ? 'alert' : 'status' }}"><div><strong>{{ $title }}</strong><p>{{ session($tone) }}</p></div><button type="button" data-dismiss aria-label="Cerrar aviso">×</button></div>
                @endif
            @endforeach
            @if($errors->any())
                <div class="alert error validation-summary" role="alert" tabindex="-1"><strong>Revisa los datos del formulario</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Corrige los campos indicados e intenta guardar nuevamente.</p></div>
            @endif
            @yield('content')
        </main>
        <footer class="app-footer"><div><img src="{{ asset('images/logo-gantek.png') }}" alt="GanTek"><span><strong>Tecnología para un campo más productivo.</strong><small>Tu ganado, en buenas manos.</small></span></div><small>© {{ date('Y') }} GanTek · Gestión ganadera</small></footer>
    </div>
</div>
<dialog id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-record confirm-description">
    <div class="dialog-heading"><span class="dialog-symbol" aria-hidden="true">!</span><button type="button" data-cancel aria-label="Cerrar confirmación">×</button></div>
    <h2 id="confirm-title">Confirmar acción</h2><p id="confirm-record"></p><p id="confirm-description"></p>
    <div class="dialog-actions"><button type="button" class="btn" data-cancel autofocus>Cancelar</button><button type="button" class="btn danger" data-confirm>Confirmar</button></div>
</dialog>
@stack('scripts')
</body>
</html>
