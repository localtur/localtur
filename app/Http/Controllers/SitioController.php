<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Localidad;
use App\Support\Marca;
use Illuminate\Http\Response;

class SitioController extends Controller
{
    public function inicio()
    {
        $localidades = Localidad::query()
            ->withCount(['avisos' => fn ($q) => $q->publicados()])
            ->with('comuna.region')
            ->orderByDesc('avisos_count')
            ->limit(24)
            ->get()
            ->filter(fn ($l) => $l->avisos_count > 0);

        return view('sitio.inicio', [
            'marca' => Marca::actual(),
            'localidades' => $localidades,
            'categorias' => config('marca.categorias'),
        ]);
    }

    public function listado(string $categoria, string $localidad)
    {
        $clave = Marca::categoriaPorSlug($categoria) ?? abort(404);
        $loc = Localidad::where('slug', $localidad)->with('comuna.region')->firstOrFail();

        $avisos = Aviso::publicados()
            ->where('localidad_id', $loc->id)
            ->where('categoria', $clave)
            // primero los activados, después los "sin activar"
            ->orderByRaw("case when estado in ('completo','anual') then 0 when estado = 'activo_gratis' then 1 else 2 end")
            ->orderBy('titulo')
            ->paginate(30);

        return view('sitio.listado', [
            'marca' => Marca::actual(),
            'categoria' => Marca::categoria($clave),
            'localidad' => $loc,
            'avisos' => $avisos,
        ]);
    }

    public function aviso(string $categoria, string $localidad, string $slug)
    {
        $clave = Marca::categoriaPorSlug($categoria) ?? abort(404);
        $aviso = Aviso::publicados()
            ->where('categoria', $clave)
            ->where('slug', $slug)
            ->whereHas('localidad', fn ($q) => $q->where('slug', $localidad))
            ->with('localidad.comuna.region')
            ->firstOrFail();

        $cercanos = Aviso::publicados()
            ->where('localidad_id', $aviso->localidad_id)
            ->where('id', '!=', $aviso->id)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        return view('sitio.aviso', [
            'marca' => Marca::actual(),
            'categoria' => Marca::categoria($clave),
            'aviso' => $aviso,
            'cercanos' => $cercanos,
        ]);
    }

    public function robots(): Response
    {
        $txt = Marca::indexar()
            ? "User-agent: *\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
