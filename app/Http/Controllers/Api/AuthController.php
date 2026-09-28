<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    /**
     * Registro normal desde la aplicación móvil.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Inicio de sesión normal con correo y contraseña.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($credentials['email']));

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('gantek-mobile')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Inicio de sesión con Google.
     *
     * Flutter inicia sesión con Google mediante Firebase Authentication.
     * Después envía el Firebase ID Token a este endpoint.
     */
    public function google(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            /*
             * Creamos Firebase Auth utilizando la cuenta de servicio
             * configurada mediante FIREBASE_CREDENTIALS.
             */
            $credentials = config('firebase.projects.app.credentials');

            $firebaseAuth = (new Factory)
                ->withServiceAccount($credentials)
                ->createAuth();

            /*
             * Verificamos criptográficamente el Firebase ID Token.
             * No confiamos simplemente en un email enviado por Flutter.
             */
            $verifiedIdToken = $firebaseAuth->verifyIdToken(
                $data['id_token']
            );

            $uid = $verifiedIdToken->claims()->get('sub');

            if (! $uid) {
                return response()->json([
                    'message' => 'No se pudo identificar al usuario de Firebase.',
                ], 401);
            }

            /*
             * Verificamos que este endpoint se esté utilizando realmente
             * con una sesión iniciada mediante Google.
             */
            $firebaseClaim = $verifiedIdToken->claims()->get('firebase');

            $signInProvider = is_array($firebaseClaim)
                ? ($firebaseClaim['sign_in_provider'] ?? null)
                : null;

            if ($signInProvider !== 'google.com') {
                return response()->json([
                    'message' => 'El token recibido no corresponde a un inicio de sesión con Google.',
                ], 401);
            }

            /*
             * Obtenemos los datos directamente desde Firebase Admin.
             */
            $firebaseUser = $firebaseAuth->getUser($uid);

            $email = strtolower(trim((string) $firebaseUser->email));

            if ($email === '') {
                return response()->json([
                    'message' => 'La cuenta de Google no proporcionó un correo electrónico.',
                ], 422);
            }

            /*
             * Para vincular de forma segura una cuenta existente por correo,
             * Firebase debe indicar que el correo está verificado.
             */
            if (! $firebaseUser->emailVerified) {
                return response()->json([
                    'message' => 'El correo de la cuenta de Google no está verificado.',
                ], 403);
            }

            $name = trim((string) $firebaseUser->displayName);

            if ($name === '') {
                $name = 'Usuario Google';
            }

            /*
             * Si el usuario ya existe en MySQL, reutilizamos su cuenta.
             * Si no existe, lo registramos automáticamente.
             */
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,

                    // Google será el método de autenticación.
                    // Se genera una contraseña aleatoria que el usuario desconoce.
                    'password' => Hash::make(Str::random(64)),
                ]);
            }

            /*
             * Generamos el token Sanctum que utilizará Flutter
             * para consumir /api/dashboard, /api/ganado, etc.
             */
            $token = $user->createToken('gantek-mobile')->plainTextToken;

            return response()->json([
                'message' => 'Inicio de sesión con Google exitoso.',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No fue posible validar la sesión de Google.',
            ], 401);
        }
    }


    /**
 * Inicio de sesión con Facebook mediante Firebase Authentication.
 */
public function facebook(Request $request): JsonResponse
{
    $data = $request->validate([
        'id_token' => ['required', 'string'],
    ]);

    try {
        // Crear Firebase Auth usando la cuenta de servicio.
        $credentials = config('firebase.projects.app.credentials');

        $firebaseAuth = (new Factory)
            ->withServiceAccount($credentials)
            ->createAuth();

        // Verificar el Firebase ID Token recibido desde Flutter.
        $verifiedIdToken = $firebaseAuth->verifyIdToken(
            $data['id_token']
        );

        $uid = $verifiedIdToken->claims()->get('sub');

        if (! $uid) {
            return response()->json([
                'message' => 'No se pudo identificar al usuario de Firebase.',
            ], 401);
        }

        // Comprobar que el inicio de sesión realmente proviene de Facebook.
        $firebaseClaim = $verifiedIdToken->claims()->get('firebase');

        $signInProvider = is_array($firebaseClaim)
            ? ($firebaseClaim['sign_in_provider'] ?? null)
            : null;

        if ($signInProvider !== 'facebook.com') {
            return response()->json([
                'message' => 'El token recibido no corresponde a un inicio de sesión con Facebook.',
            ], 401);
        }

        // Obtener los datos del usuario directamente desde Firebase.
        $firebaseUser = $firebaseAuth->getUser($uid);

        $email = strtolower(
            trim((string) $firebaseUser->email)
        );

        if ($email === '') {
            return response()->json([
                'message' => 'Facebook no proporcionó un correo electrónico.',
            ], 422);
        }

        $name = trim(
            (string) $firebaseUser->displayName
        );

        if ($name === '') {
            $name = 'Usuario Facebook';
        }

        // Buscar usuario existente en MySQL.
        $user = User::whereRaw(
            'LOWER(email) = ?',
            [$email]
        )->first();

        // Si no existe, crear su cuenta.
        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(
                    Str::random(64)
                ),
            ]);
        }

        // Generar token Sanctum para la aplicación móvil.
        $token = $user
            ->createToken('gantek-mobile')
            ->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión con Facebook exitoso.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'message' => 'No fue posible validar la sesión de Facebook.',
        ], 401);
    }
}
    /**
     * Cierra la sesión actual de la aplicación móvil.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}