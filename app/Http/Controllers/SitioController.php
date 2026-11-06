<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Localidad;
use App\Support\Marca;
use App\Support\Pais;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitioController extends Controller
{
    /** localtur.com/ → elegir país. */
    public function paises()
    {
        return view('sitio.paises', [
            'marca' => Marca::actual(),
            'paises' => Pais::todos(),
            'canonica' => url('/'),
        ]);
    }

    public function inicio()
    {
        $pais = Pais::actual();
        $localidades = Localidad::query()
            ->visibles()
            ->withCount(['avisos' => fn ($q) => $q->publicados()])
            ->with('comuna.region')
            ->orderByDesc('avisos_count')
            ->limit(24)
            ->get()
            ->filter(fn ($l) => $l->avisos_count > 0);

        return view('sitio.inicio', [
            'marca' => Marca::actual(),
            'pais' => $pais,
            'localidades' => $localidades,
            'categorias' => config('marca.categorias'),
            'otras' => Pais::otrasMarcas(),
            'canonica' => Pais::ruta('inicio'),
        ]);
    }

    public function listado(string $categoria, string $localidad)
    {
        $pais = Pais::actual();
        $clave = Marca::categoriaPorSlug($categoria) ?? abort(404);
        $loc = Localidad::visibles()->where('slug', $localidad)->with('comuna.region')->firstOrFail();

        $avisos = Aviso::publicados()
            ->where('localidad_id', $loc->id)
            ->where('categoria', $clave)
            // primero los activados, después los "sin activar"
            ->orderByRaw("case when estado in ('completo','anual') then 0 when estado = 'activo_gratis' then 1 else 2 end")
            ->orderBy('titulo')
            ->paginate(30);

        $params = [$categoria, $loc->slug];

        return view('sitio.listado', [
            'marca' => Marca::actual(),
            'pais' => $pais,
            'categoria' => Marca::categoria($clave),
            'localidad' => $loc,
            'avisos' => $avisos,
            'canonica' => Pais::ruta('listado', $params),
        ]);
    }

    public function aviso(string $categoria, string $localidad, string $slug)
    {
        $pais = Pais::actual();
        $clave = Marca::categoriaPorSlug($categoria) ?? abort(404);
        $aviso = Aviso::publicados()
            ->where('categoria', $clave)
            ->where('slug', $slug)
            ->whereHas('localidad', fn ($q) => $q->visibles()->where('slug', $localidad))
            ->with('localidad.comuna.region')
            ->firstOrFail();

        $cercanos = Aviso::publicados()
            ->where('localidad_id', $aviso->localidad_id)
            ->where('id', '!=', $aviso->id)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $params = [$categoria, $aviso->localidad->slug, $aviso->slug];

        return view('sitio.aviso', [
            'marca' => Marca::actual(),
            'pais' => $pais,
            'categoria' => Marca::categoria($clave),
            'aviso' => $aviso,
            'cercanos' => $cercanos,
            'canonica' => Pais::ruta('aviso.ver', $params),
        ]);
    }

    /** www.alojarse.cl/... → https://alojarse.cl/... */
    public function aDominioPrincipal(Request $request)
    {
        foreach (config('marca.marcas') as $m) {
            if (in_array($request->getHost(), $m['alias'] ?? [], true)) {
                return redirect()->away('https://'.$m['dominio'].$request->getRequestUri(), 301);
            }
        }
        abort(404);
    }

    public function robots(): Response
    {
        $txt = Marca::indexar()
            ? "User-agent: *\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
