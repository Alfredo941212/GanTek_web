<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>GanTek - Iniciar Sesión</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* =====================================================
           CONFIGURACIÓN GENERAL
        ===================================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .login-page {
            min-height: 100vh;
            background: #f7f8f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }


        /* =====================================================
           DECORACIÓN / IMAGEN DEL GANADO
        ===================================================== */

        .login-decoration {
            position: absolute;
            left: 0;
            top: 0;
            width: 52%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .login-cow-image {
            position: absolute;

            left: -160px;
            top: -130px;

            width: 780px;
            height: 780px;

            background-image: url("{{ asset('images/login-ganado.png') }}");
            background-size: cover;
            background-position: center;

            border-radius: 0 0 100% 0;

            opacity: 0.95;
        }

        /*
         * Contorno muy suave para que la imagen
         * se integre con el fondo blanco.
         */

        .login-decoration::after {
            content: "";

            position: absolute;

            left: -175px;
            top: -145px;

            width: 810px;
            height: 810px;

            border-radius: 0 0 100% 0;

            border-right: 2px solid rgba(24, 91, 69, 0.10);
            border-bottom: 2px solid rgba(24, 91, 69, 0.10);
        }


        /* =====================================================
           TEXTO DECORATIVO
        ===================================================== */

        .login-slogan {
            position: absolute;

            left: 55px;
            bottom: 65px;

            display: flex;
            flex-direction: column;

            gap: 3px;

            color: #527466;

            font-size: 16px;

            letter-spacing: 3px;

            line-height: 1.5;
        }

        .login-slogan strong {
            color: #185b45;

            font-size: 23px;

            letter-spacing: 4px;
        }


        /* =====================================================
           TARJETA DEL LOGIN
        ===================================================== */

        .login-card {
            position: relative;

            z-index: 10;

            width: 430px;

            margin-left: 32%;

            padding: 35px 40px 30px;

            background: rgba(255, 255, 255, 0.98);

            border-radius: 18px;

            border: 1px solid rgba(24, 91, 69, 0.08);

            box-shadow:
                0 18px 45px rgba(28, 54, 43, 0.13);
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .login-logo {
            display: flex;
            justify-content: center;
            align-items: center;

            margin-bottom: 8px;
        }

        .logo-circle {
            width: 82px;
            height: 82px;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 50%;

            background: #f5f8f5;

            border: 2px solid rgba(24, 91, 69, 0.12);

            box-shadow:
                0 5px 15px rgba(24, 91, 69, 0.08);
        }

        .logo-circle img {
            width: 68px;
            height: 68px;

            object-fit: contain;

            border-radius: 50%;
        }


        /* =====================================================
           TÍTULOS
        ===================================================== */

        .login-title {
            margin: 0;

            text-align: center;

            color: #185b45;

            font-size: 25px;

            font-weight: 800;
        }

        .login-welcome {
            margin-top: 10px;
            margin-bottom: 5px;

            text-align: center;

            color: #26352e;

            font-size: 25px;

            font-weight: 700;
        }

        .login-subtitle {
            margin-top: 0;
            margin-bottom: 25px;

            text-align: center;

            color: #727b76;

            font-size: 14px;
        }


        /* =====================================================
           MENSAJES DE ERROR
        ===================================================== */

        .login-error {
            margin-bottom: 18px;

            padding: 11px 13px;

            border-radius: 9px;

            font-size: 13px;
        }


        /* =====================================================
           CAMPOS
        ===================================================== */

        .login-field {
            margin-bottom: 17px;
        }

        .login-field label {
            display: block;

            margin-bottom: 7px;

            color: #26352e;

            font-size: 14px;

            font-weight: 700;
        }

        .login-input {
            width: 100%;

            padding: 12px 14px;

            border: 1px solid #d4ddd8;

            border-radius: 9px;

            outline: none;

            background: #ffffff;

            color: #26352e;

            font-size: 14px;

            transition: all 0.2s ease;
        }

        .login-input:focus {
            border-color: #287154;

            box-shadow:
                0 0 0 3px rgba(40, 113, 84, 0.10);
        }


        /* =====================================================
           RECUPERAR CONTRASEÑA
        ===================================================== */

        .forgot-password {
            text-align: right;

            margin-top: -3px;

            margin-bottom: 17px;
        }

        .forgot-password a {
            color: #1d684d;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }


        /* =====================================================
           RECORDARME
        ===================================================== */

        .remember-container {
            margin-bottom: 21px;
        }

        .remember-container label {
            display: flex;

            align-items: center;

            gap: 8px;

            color: #59635e;

            font-size: 14px;

            cursor: pointer;
        }

        .remember-container input {
            width: 15px;
            height: 15px;

            accent-color: #1d684d;

            cursor: pointer;
        }


        /* =====================================================
           BOTÓN INICIAR SESIÓN
        ===================================================== */

        .login-button {
            width: 100%;

            padding: 12px 16px;

            border: none;

            border-radius: 9px;

            background: #1d684d;

            color: white;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            transition: all 0.2s ease;
        }

        .login-button:hover {
            background: #14513c;

            transform: translateY(-1px);

            box-shadow:
                0 7px 17px rgba(29, 104, 77, 0.20);
        }


        /* =====================================================
           REGISTRO
        ===================================================== */

        .register-text {
            margin-top: 19px;

            text-align: center;

            color: #68736d;

            font-size: 13px;
        }

        .register-text a {
            color: #1d684d;

            font-weight: 700;

            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }


        /* =====================================================
           SEPARADOR
        ===================================================== */

        .social-divider {
            display: flex;

            align-items: center;

            gap: 12px;

            margin: 23px 0 17px;
        }

        .social-divider::before,
        .social-divider::after {
            content: "";

            flex: 1;

            height: 1px;

            background: #d9dfdc;
        }

        .social-divider span {
            color: #7a837e;

            font-size: 13px;

            white-space: nowrap;
        }


        /* =====================================================
           BOTONES GOOGLE / FACEBOOK
        ===================================================== */

        .social-buttons {
            display: flex;

            flex-direction: column;

            gap: 10px;
        }

        .social-button {
            width: 100%;

            min-height: 43px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 10px;

            padding: 10px 14px;

            border: 1px solid #d5ddd9;

            border-radius: 9px;

            background: #ffffff;

            color: #34413b;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;

            transition: all 0.2s ease;
        }

        .social-button:hover {
            background: #f7faf8;

            border-color: #b9c9c1;

            transform: translateY(-1px);
        }

        .social-button svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .login-decoration {
                width: 100%;
                opacity: 0.15;
            }

            .login-slogan {
                display: none;
            }

            .login-card {
                margin-left: 0;

                width: min(430px, 88vw);
            }
        }


        @media (max-width: 500px) {

            .login-card {
                width: 92vw;

                padding: 28px 23px 25px;
            }

            .logo-circle {
                width: 72px;
                height: 72px;
            }

            .logo-circle img {
                width: 59px;
                height: 59px;
            }

            .login-title {
                font-size: 23px;
            }

            .login-welcome {
                font-size: 22px;
            }
        }
    </style>
</head>


<body>

    <div class="login-page">

        <!-- =================================================
             IMAGEN DECORATIVA
        ================================================== -->

        <div class="login-decoration">

            <div class="login-cow-image"></div>

            <div class="login-slogan">
                <span>TECNOLOGÍA</span>
                <strong>GANADERA</strong>
                <span>INTELIGENTE</span>
            </div>

        </div>


        <!-- =================================================
             TARJETA DE LOGIN
        ================================================== -->

        <div class="login-card">


            <!-- LOGO -->

            <div class="login-logo">

                <div class="logo-circle">

                    <img
                        src="{{ asset('images/logo-gantek.png') }}"
                        alt="Logo GanTek">

                </div>

            </div>


            <!-- TÍTULO -->

            <h1 class="login-title">
                GanTek
            </h1>

            <h2 class="login-welcome">
                Bienvenido
            </h2>

            <p class="login-subtitle">
                Inicia sesión para administrar tu ganado.
            </p>


            <!-- =================================================
                 MENSAJE DE ERROR DE SESIÓN
            ================================================== -->

            @if (session('error'))

                <div class="login-error bg-red-100 border border-red-400 text-red-700">

                    {{ session('error') }}

                </div>

            @endif


            <!-- =================================================
                 ERRORES DE VALIDACIÓN
            ================================================== -->

            @if ($errors->any())

                <div
                    class="login-error bg-red-100 border border-red-400 text-red-700"
                    role="alert">

                    @foreach ($errors->all() as $error)

                        <p>{{ $error }}</p>

                    @endforeach

                </div>

            @endif


            <!-- =================================================
                 FORMULARIO
                 NO SE MODIFICÓ SU FUNCIONALIDAD
            ================================================== -->

            <form
                action="{{ route('login.process') }}"
                method="POST">

                @csrf


                <!-- CORREO -->

                <div class="login-field">

                    <label>
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="login-input"
                        required>

                </div>


                <!-- CONTRASEÑA -->

                <div class="login-field">

                    <label>
                        Contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="login-input"
                        required>

                </div>


                <!-- RECUPERAR CONTRASEÑA -->

                <div class="forgot-password">

                    <a href="{{ route('password.request') }}">
                        ¿Olvidaste tu contraseña?
                    </a>

                </div>


                <!-- RECORDARME -->

                <div class="remember-container">

                    <label>

                        <input
                            type="checkbox"
                            name="remember">

                        <span>
                            Recordarme
                        </span>

                    </label>

                </div>


                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="login-button">

                    Iniciar sesión

                </button>

            </form>


            <!-- =================================================
                 REGISTRO
            ================================================== -->

            <div class="register-text">

                ¿No tienes una cuenta?

                <a href="{{ route('register') }}">
                    Regístrate
                </a>

            </div>


            <!-- =================================================
                 REDES SOCIALES
            ================================================== -->

            <div class="social-divider">

                <span>
                    O continúa con
                </span>

            </div>


            <div class="social-buttons">


                <!-- GOOGLE -->

                <a
                    href="{{ route('auth.google') }}"
                    class="social-button">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4" />

                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853" />

                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05" />

                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335" />

                    </svg>

                    Continuar con Google

                </a>


                <!-- FACEBOOK -->

                <a
                    href="{{ route('auth.facebook') }}"
                    class="social-button">

                    <svg
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        style="color:#1877F2;">

                        <path
                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />

                    </svg>

                    Continuar con Facebook

                </a>

            </div>

        </div>

    </div>

</body>

</html>