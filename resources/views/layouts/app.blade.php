<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GanTek Web')</title>
    <link rel="stylesheet" href="{{ asset('css/gantek.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">GanTek</div>
        <nav>
            <a href="{{ route('dashboard') }}">Inicio</a>
            <a href="{{ route('ganado.index') }}">Ganado</a>
            <a href="{{ route('vacunas.index') }}">Vacunación</a>
            <a href="{{ route('ventas.index') }}">Ventas</a>
            <a href="{{ route('reportes.index') }}">Reportes</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-btn" type="submit">Cerrar sesión</button>
        </form>
    </aside>

    <main class="content">
        <header class="topbar">
            <div>
                <h1>@yield('heading', 'GanTek')</h1>
                <p>Sistema web de gestión ganadera</p>
            </div>
            <div class="user-chip">{{ auth()->user()->name ?? 'Usuario' }}</div>
        </header>

        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error">
                <strong>Corrige lo siguiente:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
