<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GanTek - Crear cuenta</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">

        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">

            <h1 class="text-2xl font-bold text-center mb-6">
                GanTek
            </h1>

            <h2 class="text-xl font-semibold text-center mb-2">
                Crear cuenta
            </h2>

            <p class="text-gray-600 text-center mb-6">
                Regístrate para comenzar a administrar tu ganado.
            </p>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-5">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.process') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="name"
                           class="block text-gray-700 text-sm font-bold mb-2">
                        Nombre completo
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        maxlength="150"
                        autocomplete="name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div class="mb-4">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label for="password"
                           class="block text-gray-700 text-sm font-bold mb-2">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        minlength="8"
                        autocomplete="new-password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >

                    <p class="mt-1 text-xs text-gray-500">
                        Mínimo 8 caracteres, con mayúscula, minúscula y número.
                    </p>
                </div>

                <div class="mb-6">
                    <label for="password_confirmation"
                           class="block text-gray-700 text-sm font-bold mb-2">
                        Confirmar contraseña
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        minlength="8"
                        autocomplete="new-password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg
                           hover:bg-blue-700 transition duration-200">
                    Crear cuenta
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?

                    <a href="{{ route('login') }}"
                       class="text-blue-600 font-semibold hover:underline">
                        Inicia sesión
                    </a>
                </p>
            </div>

        </div>
    </div>
</body>
</html>