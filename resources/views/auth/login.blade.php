<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | GanTek</title>
    <link rel="stylesheet" href="{{ asset('css/gantek.css') }}">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-logo">GanTek</div>
    <h1>Bienvenido</h1>
    <p>Inicia sesión para administrar tu ganado.</p>

    @if($errors->any())
        <div class="alert error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.process') }}">
        @csrf
        <label>Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <label class="remember">
            <input type="checkbox" name="remember"> Recordarme
        </label>

        <button type="submit" class="btn primary full">Iniciar sesión</button>
    </form>
</div>
</body>
</html>
