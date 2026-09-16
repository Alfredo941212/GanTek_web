<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GanTek - Recuperar contraseña</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <div class="min-h-screen flex items-center justify-center px-4">

        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">

            <h1 class="text-2xl font-bold text-center mb-6">
                GanTek
            </h1>

            <h2 class="text-xl font-semibold text-center mb-2">
                Recuperar contraseña
            </h2>

            <p class="text-gray-600 text-center mb-6">
                Ingresa el correo electrónico asociado a tu cuenta.
            </p>

            {{-- Mensaje después de solicitar la recuperación --}}
            @if (session('status'))
                <div class="bg-green-100 border border-green-300 text-green-700 px-4 py-3 rounded-lg mb-5">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Errores de validación --}}
            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-5">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">

                @csrf

                <div class="mb-6">

                    <label for="email"
                           class="block text-gray-700 text-sm font-bold mb-2">
                        Correo electrónico
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        maxlength="255"
                        autocomplete="email"
                        placeholder="ejemplo@correo.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                        autofocus
                    >

                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg
                           hover:bg-blue-700 transition duration-200">
                    Enviar enlace de recuperación
                </button>

            </form>

            <div class="mt-6 text-center">

                <a href="{{ route('login') }}"
                   class="text-sm text-blue-600 font-semibold hover:underline">
                    Volver a iniciar sesión
                </a>

            </div>

        </div>

    </div>

</body>
</html>