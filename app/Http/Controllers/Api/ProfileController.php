<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('2mb'),
            ],
        ]);

        $user = $request->user();

        $user->name = trim($validated['name']);
        $user->phone = $validated['phone'] ?? null;

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;

            $newAvatar = $request->file('avatar')
                ->store('avatars', 'public');

            $user->avatar = $newAvatar;
        }

        $user->save();

        if (
            isset($newAvatar, $oldAvatar) &&
            $oldAvatar !== $newAvatar &&
            str_starts_with($oldAvatar, 'avatars/')
        ) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'data' => $user->fresh(),
        ]);
    }
}