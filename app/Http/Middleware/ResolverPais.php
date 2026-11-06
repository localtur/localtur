<?php

namespace App\Http\Middleware;

use App\Support\Marca;
use App\Support\Pais;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Fija marca y país de la petición y saca {pais} de los parámetros para que los controladores no lo reciban. */
class ResolverPais
{
    public function handle(Request $request, Closure $next): Response
    {
        Marca::reiniciar();
        Pais::reiniciar();
        $ruta = $request->route();
        if ($ruta && $ruta->hasParameter('pais')) {
            $codigo = $ruta->parameter('pais');
            abort_unless(in_array($codigo, Pais::codigos(), true), 404);
            Pais::fijar($codigo);
            $ruta->forgetParameter('pais');
            if ($request->hasSession()) {
                $request->session()->put('pais', $codigo); // las páginas de cuenta recuerdan el último país visitado
            }
        } elseif (Pais::multipais() && $request->hasSession() && in_array($request->session()->get('pais'), Pais::codigos(), true)) {
            Pais::fijar($request->session()->get('pais'));
        }

        return $next($request);
    }
}
