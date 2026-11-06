<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Comuna;
use App\Models\Localidad;
use App\Models\Pais as PaisModelo;
use App\Models\Reclamo;
use App\Models\Region;
use App\Support\Fotos;
use App\Support\Marca;
use App\Support\Pais;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Lo que hace el dueño con sus avisos: reclamar uno existente, crear uno nuevo, editarlo y subir fotos. */
class AvisoDuenoController extends Controller
{
    private function puedeEditar(Request $request, Aviso $aviso): void
    {
        $u = $request->user();
        abort_unless($aviso->user_id === $u->id || $u->esEquipo(), 403);
    }

    // ── Reclamar ("¿Es tu negocio?") ─────────────────────────────

    public function formReclamar(Request $request, Aviso $aviso)
    {
        $aviso->load('localidad.comuna.region');
        $previo = $aviso->reclamos()->where('user_id', $request->user()->id)->first();

        return CuentaController::vista('cuenta.reclamar', ['aviso' => $aviso, 'previo' => $previo]);
    }

    public function reclamar(Request $request, Aviso $aviso)
    {
        if ($aviso->user_id) {
            return back()->withErrors(['relacion' => 'Este aviso ya tiene un responsable. Si crees que es un error, escríbenos.']);
        }
        $datos = $request->validate([
            'relacion' => ['required', Rule::in(array_keys(Reclamo::RELACIONES))],
            'telefono' => ['required', 'string', 'min:8', 'max:30'],
            'mensaje' => ['nullable', 'string', 'max:1000'],
            'acepto' => ['accepted'],
        ], ['acepto.accepted' => 'Debes confirmar que estás autorizado a administrar este negocio.']);

        Reclamo::updateOrCreate(
            ['aviso_id' => $aviso->id, 'user_id' => $request->user()->id],
            ['relacion' => $datos['relacion'], 'telefono' => $datos['telefono'], 'mensaje' => $datos['mensaje'] ?? null, 'estado' => 'pendiente']
        );

        return redirect()->route('cuenta.inicio')->with('ok', 'Recibimos tu solicitud. Te avisaremos cuando la revisemos (normalmente en 1 a 2 días hábiles).');
    }

    // ── Crear ────────────────────────────────────────────────────

    public function formNuevo()
    {
        $codigo = Pais::codigo();
        $regiones = Region::whereHas('pais', fn ($p) => $p->where('codigo', strtoupper($codigo)))->orderBy('nombre')->pluck('nombre');

        return CuentaController::vista('cuenta.aviso-nuevo', [
            'categorias' => config('marca.categorias'),
            'regiones' => $regiones,
            'comunas' => Comuna::whereHas('region.pais', fn ($p) => $p->where('codigo', strtoupper($codigo)))->orderBy('nombre')->pluck('nombre')->unique(),
        ]);
    }

    public function crear(Request $request)
    {
        $datos = $request->validate([
            'categoria' => ['required', Rule::in(array_keys(config('marca.categorias')))],
            'subtipo' => ['nullable', 'string', 'max:80'],
            'titulo' => ['required', 'string', 'min:3', 'max:60'],
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
            'localidad' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string', 'max:190'],
            'acepto' => ['accepted'],
        ], ['acepto.accepted' => 'Debes confirmar que el negocio es tuyo o que estás autorizado.']);

        $codigo = strtoupper(Pais::codigo());
        $p = Pais::actual();
        $pais = PaisModelo::firstOrCreate(['codigo' => $codigo], ['nombre' => $p['nombre'], 'moneda' => $p['moneda']]);
        $region = Region::firstOrCreate(['pais_id' => $pais->id, 'slug' => Str::slug($datos['region'])], ['nombre' => trim($datos['region'])]);
        $comuna = Comuna::firstOrCreate(['region_id' => $region->id, 'slug' => Str::slug($datos['comuna'])], ['nombre' => trim($datos['comuna'])]);
        $nombreLoc = trim($datos['localidad'] ?? '') ?: $comuna->nombre;
        $localidad = Localidad::firstOrCreate(['comuna_id' => $comuna->id, 'slug' => Str::slug($nombreLoc)], ['nombre' => $nombreLoc]);

        $aviso = Aviso::create([
            'localidad_id' => $localidad->id,
            'user_id' => $request->user()->id,
            'categoria' => $datos['categoria'],
            'subtipo' => $datos['subtipo'] ?? null,
            'titulo' => $datos['titulo'],
            'slug' => Aviso::slugLibre($datos['titulo'], $localidad->id, $datos['categoria']),
            'direccion' => $datos['direccion'] ?? null,
            'descripcion_corta' => Str::limit(($datos['subtipo'] ?: Marca::categoria($datos['categoria'])['nombre']).' en '.$localidad->nombre.', '.$region->nombre.'.', 155, ''),
            'estado' => 'activo_gratis',
            'origen' => 'propio',
            'imagen_referencial' => Str::slug($datos['categoria'].'-general').'-centro',
        ]);
        if ($request->user()->rol === 'viajero') {
            $request->user()->forceFill(['rol' => 'anfitrion'])->save();
        }

        return redirect()->route('cuenta.aviso.editar', $aviso)->with('ok', 'Aviso creado. Ahora agrega una descripción, tus datos de contacto y tus fotos.');
    }

    // ── Editar ───────────────────────────────────────────────────

    public function formEditar(Request $request, Aviso $aviso)
    {
        $this->puedeEditar($request, $aviso);
        $aviso->load('localidad.comuna.region.pais');

        return CuentaController::vista('cuenta.aviso-editar', ['aviso' => $aviso, 'max' => Aviso::MAX_FOTOS]);
    }

    public function actualizar(Request $request, Aviso $aviso)
    {
        $this->puedeEditar($request, $aviso);
        $actuales = array_values(array_filter((array) $aviso->fotos));
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:60'],
            'subtipo' => ['nullable', 'string', 'max:80'],
            'descripcion_corta' => ['nullable', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'direccion' => ['nullable', 'string', 'max:190'],
            'contacto_telefono' => ['nullable', 'string', 'max:30'],
            'contacto_whatsapp' => ['nullable', 'string', 'max:30'],
            'contacto_correo' => ['nullable', 'email', 'max:190'],
            'contacto_web' => ['nullable', 'url', 'max:190'],
            'borrar' => ['array'],
            'borrar.*' => ['string'],
            'portada' => ['nullable', 'string'],
            'fotos_nuevas' => ['array', 'max:'.Aviso::MAX_FOTOS],
            'fotos_nuevas.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'fotos_nuevas.*.image' => 'Cada archivo debe ser una imagen (JPG, PNG o WebP).',
            'fotos_nuevas.*.max' => 'Cada foto puede pesar hasta 8 MB.',
            'contacto_web.url' => 'La web debe empezar con https://',
        ]);

        // Fotos: quitar las marcadas, agregar nuevas hasta 10, elegir portada
        $borrar = array_intersect($datos['borrar'] ?? [], $actuales);
        foreach ($borrar as $b) {
            Fotos::borrar($b);
        }
        $fotos = array_values(array_diff($actuales, $borrar));
        $nuevas = $request->file('fotos_nuevas', []);
        if (count($fotos) + count($nuevas) > Aviso::MAX_FOTOS) {
            return back()->withInput()->withErrors(['fotos_nuevas' => 'Puedes tener hasta '.Aviso::MAX_FOTOS.' fotos. Quita alguna antes de subir más.']);
        }
        foreach ($nuevas as $archivo) {
            $fotos[] = Fotos::guardar($archivo, $aviso);
        }
        if (($p = $datos['portada'] ?? null) && in_array($p, $fotos, true)) {
            $fotos = array_values(array_unique(array_merge([$p], $fotos)));
        }

        unset($datos['borrar'], $datos['portada'], $datos['fotos_nuevas']);
        if ($datos['titulo'] !== $aviso->titulo && $aviso->origen === 'propio' && ! $aviso->indexable) {
            // mientras no esté indexado, el slug puede seguir al título
            $datos['slug'] = Aviso::slugLibre($datos['titulo'], $aviso->localidad_id, $aviso->categoria, $aviso->id);
        }
        $aviso->update($datos + ['fotos' => $fotos]);

        return redirect()->route('cuenta.aviso.editar', $aviso)->with('ok', 'Cambios guardados.');
    }
}
