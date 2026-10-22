<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
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

        // Eliminar únicamente fotografías locales anteriores.
        if (
            isset($newAvatar, $oldAvatar) &&
            $oldAvatar !== $newAvatar &&
            str_starts_with($oldAvatar, 'avatars/')
        ) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }
}
