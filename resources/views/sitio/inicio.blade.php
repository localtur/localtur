@php
  $multi = \App\Support\Pais::multipais();
  $titulo = $multi ? 'LocalTur.com '.$pais['nombre'].' · '.$pais['eslogan'] : $marca['nombre'].' · '.$marca['eslogan'];
@endphp
@extends('sitio.layout', ['titulo' => $titulo, 'descripcion' => $multi ? $pais['eslogan'] : $marca['eslogan']])

@section('contenido')
<section class="hero">
  <h1>{{ $multi ? $pais['eslogan'] : $marca['eslogan'] }}</h1>
  <p>Alojamientos, lugares para comer, actividades, comercio y servicios. Contacto directo con quien atiende, sin intermediarios.</p>
  @if ($multi)@include('sitio._pin', ['bandera' => $pais['bandera'], 'alto' => 120])@endif
</section>

@if ($localidades->isNotEmpty())
<h2>Destinos</h2>
<div class="grilla">
@foreach ($localidades as $l)
  <div class="tarjeta">
    <h3>{{ $l->nombre }}</h3>
    <p>{{ $l->comuna->nombre !== $l->nombre ? $l->comuna->nombre.', ' : '' }}{{ $l->comuna->region->nombre }} · {{ $l->avisos_count }} avisos</p>
    <div class="cats" style="margin-bottom:0">
    @foreach ($categorias as $c)
      <a href="{{ \App\Support\Pais::ruta('listado', [$c['slug'], $l->slug]) }}">{{ $c['nombre'] }}</a>
    @endforeach
    </div>
  </div>
@endforeach
</div>
@elseif (! $pais['activo'])
<h2>Estamos llegando a {{ $pais['nombre'] }}</h2>
<p>¿Tienes un alojamiento, restaurante, tour, comercio o servicio en {{ $pais['nombre'] }}? Muy pronto podrás publicarlo gratis y recibir contactos directos, sin comisiones.</p>
<h3 style="font-size:16px;margin:20px 0 10px">Primeros destinos</h3>
<div class="chips">@foreach ($pais['destinos'] as $d)<span>{{ $d }}</span>@endforeach</div>
@else
<p>Todavía no hay avisos cargados.</p>
@endif

@foreach ($otras as $o)
<div class="cruce">
  @if (! empty($o['regiones']['solo']))
    ¿Buscas en Santiago y la Región Metropolitana? Encuéntralo en <a href="{{ $o['url'] }}">{{ $o['nombre'] }}</a>.
  @else
    ¿Buscas en el resto de {{ $pais['nombre'] }}, del norte a la Patagonia? Encuéntralo en <a href="{{ $o['url'] }}">{{ $o['nombre'] }}</a>.
  @endif
</div>
@endforeach
@endsection
