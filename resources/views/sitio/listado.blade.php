@php
  $lugar = $localidad->nombre.($localidad->comuna->nombre !== $localidad->nombre ? ', '.$localidad->comuna->nombre : '');
  $titulo = $categoria['nombre'].' en '.$lugar.' · '.$marca['nombre'];
  $descripcion = $categoria['nombre'].' en '.$lugar.', Región de '.$localidad->comuna->region->nombre.'. Contacto directo, sin comisiones.';
@endphp
@extends('sitio.layout', ['titulo' => $titulo, 'descripcion' => $descripcion])

@section('contenido')
<div class="miga"><a href="{{ \App\Support\Pais::ruta('inicio') }}">Inicio</a> › {{ $localidad->comuna->region->nombre }} › {{ $lugar }}</div>
<h1>{{ $categoria['nombre'] }} en {{ $lugar }}</h1>
<div class="cats">
@foreach (config('marca.categorias') as $c)
  <a href="{{ \App\Support\Pais::ruta('listado', [$c['slug'], $localidad->slug]) }}" @if ($c['slug'] === $categoria['slug']) style="border-color:var(--marca);color:var(--marca)" @endif>{{ $c['nombre'] }}</a>
@endforeach
</div>

@if ($avisos->isEmpty())
  <p>No hay avisos en esta categoría todavía.</p>
@else
<div class="grilla">
@foreach ($avisos as $a)
  <a class="tarjeta con-media" href="{{ \App\Support\Pais::ruta('aviso.ver', [$categoria['slug'], $localidad->slug, $a->slug]) }}">
    @include('sitio._media', ['aviso' => $a])
    <div class="txt">
    @if ($a->activado)<span class="chip">Verificado</span>@else<span class="chip gris">Sin activar</span>@endif
    <h3>{{ $a->titulo }}</h3>
    <p>{{ $a->subtipo }}</p>
    </div>
  </a>
@endforeach
</div>
<nav class="pag">{{ $avisos->links() }}</nav>
@endif
@endsection
