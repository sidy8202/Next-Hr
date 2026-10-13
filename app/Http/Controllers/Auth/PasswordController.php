<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ], [
            'password.different' => 'Le nouveau mot de passe doit être différent de l’ancien mot de passe.',
        ]);

        $user = $request->user();
        $user->password = $request->password; // Le cast 'hashed' gère le hachage
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Votre mot de passe a été mis à jour avec succès.');
    }
}
