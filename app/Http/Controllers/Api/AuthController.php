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
use Throwable;

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

    public function google(Request $request): JsonResponse
    {
        return $this->loginWithFirebase(
            $request,
            'google'
        );
    }

    public function facebook(Request $request): JsonResponse
    {
        return $this->loginWithFirebase(
            $request,
            'facebook'
        );
    }

    private function loginWithFirebase(
        Request $request,
        string $provider
    ): JsonResponse {
        $data = $request->validate([
            'id_token' => [
                'required',
                'string',
            ],
        ]);

        try {
            $credentials = env(
                'FIREBASE_CREDENTIALS'
            );

            if (
                empty($credentials) ||
                !file_exists($credentials)
            ) {
                return response()->json([
                    'message' =>
                    'Las credenciales de Firebase no están disponibles.',
                ], 500);
            }

            $auth = (new Factory())
                ->withServiceAccount(
                    $credentials
                )
                ->createAuth();

            $verifiedToken =
                $auth->verifyIdToken(
                    $data['id_token']
                );

            $uid =
                $verifiedToken
                ->claims()
                ->get('sub');

            $firebaseUser =
                $auth->getUser($uid);

            $email =
                strtolower(
                    trim(
                        $firebaseUser->email ?? ''
                    )
                );

            if ($email === '') {
                return response()->json([
                    'message' =>
                    'Firebase no proporcionó un correo electrónico.',
                ], 422);
            }

            $name =
                trim(
                    $firebaseUser->displayName
                        ?? ''
                );

            if ($name === '') {
                $name =
                    $provider === 'google'
                    ? 'Usuario Google'
                    : 'Usuario Facebook';
            }

            $user =
                User::where(
                    'email',
                    $email
                )->first();

            if (!$user) {
                $user = new User();

                $user->name = $name;
                $user->email = $email;
            }

            /*
         * No modificamos el nombre si el usuario
         * ya existe, porque GanTek permite editar
         * el perfil localmente.
         */
           /* if ($provider === 'google') {
                $user->google_id = $uid;
            }

            if ($provider === 'facebook') {
                $user->facebook_id = $uid;
            }*/
            $user->firebase_uid = $uid;

            $user->save();

            /*
         * Elimina tokens anteriores del mismo
         * dispositivo/login social para evitar
         * acumular sesiones innecesarias.
         */
            $user->tokens()
                ->where(
                    'name',
                    'mobile-social'
                )
                ->delete();

            $token =
                $user->createToken(
                    'mobile-social'
                )->plainTextToken;

            return response()->json([
                'message' =>
                'Inicio de sesión exitoso.',
                'token' => $token,
                'user' => $user,
            ]);
        } catch (Throwable $error) {

            report($error);

            return response()->json([
                'message' =>
                'No fue posible validar la cuenta con Firebase.',
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}