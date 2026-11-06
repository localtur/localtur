<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Marca;
use App\Support\Pais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CuentaController extends Controller
{
    public static function vista(string $nombre, array $datos = [])
    {
        return view($nombre, $datos + ['marca' => Marca::actual(), 'pais' => Pais::actual()]);
    }

    public function formRegistro()
    {
        return static::vista('cuenta.registro');
    }

    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)],
            'acepto' => ['accepted'],
        ], [
            'email.unique' => 'Ya existe una cuenta con ese correo. ¿Quieres ingresar?',
            'acepto.accepted' => 'Debes aceptar los términos para crear tu cuenta.',
        ]);

        $user = User::create([
            'name' => $datos['name'],
            'email' => Str::lower($datos['email']),
            'telefono' => $datos['telefono'] ?? null,
            'password' => Hash::make($datos['password']),
        ]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('cuenta.inicio'))->with('ok', '¡Bienvenido! Tu cuenta está lista.');
    }

    public function formIngreso()
    {
        return static::vista('cuenta.ingresar');
    }

    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $llave = 'ingreso|'.Str::lower($datos['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($llave, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($llave).' segundos.']);
        }
        if (! Auth::attempt(['email' => Str::lower($datos['email']), 'password' => $datos['password']], $request->boolean('recordar'))) {
            RateLimiter::hit($llave, 60);
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }
        RateLimiter::clear($llave);
        $request->session()->regenerate();

        return redirect()->intended(route('cuenta.inicio'));
    }

    public function salir(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(Pais::multipais() ? Pais::ruta('inicio') : '/');
    }

    public function inicio(Request $request)
    {
        $user = $request->user();

        return static::vista('cuenta.inicio', [
            'user' => $user,
            'avisos' => $user->avisos()->with('localidad.comuna.region.pais')->latest('updated_at')->get(),
            'reclamos' => $user->reclamos()->with('aviso.localidad')->where('estado', '!=', 'aprobado')->latest()->get(),
        ]);
    }
}
