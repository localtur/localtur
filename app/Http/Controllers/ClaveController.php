<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaClave;

/** Recuperar contraseña por correo. */
class ClaveController extends Controller
{
    public function formOlvide()
    {
        return CuentaController::vista('cuenta.olvide');
    }

    public function enviarEnlace(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        try {
            Password::sendResetLink(['email' => Str::lower($request->input('email'))]);
        } catch (\Throwable $e) {
            report($e);
        }

        // Mismo mensaje exista o no la cuenta: no revelamos qué correos están registrados.
        return back()->with('ok', __('passwords.sent'));
    }

    public function formRestablecer(Request $request, string $token)
    {
        return CuentaController::vista('cuenta.restablecer', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function restablecer(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', ReglaClave::min(10)],
        ]);
        $estado = Password::reset(
            ['email' => Str::lower($request->input('email'))] + $request->only('password', 'password_confirmation', 'token'),
            function (User $user, string $clave) {
                $user->forceFill(['password' => Hash::make($clave), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        return $estado === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', __('passwords.reset').' Ya puedes ingresar.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($estado)]);
    }
}
