<?php

namespace App\Http\Middleware;

use App\Support\Marca;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mientras el sitio sea prototipo, pide a los buscadores que no indexen ni sigan nada. */
class NoIndexar
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);
        if (! Marca::indexar()) {
            $respuesta->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $respuesta;
    }
}
