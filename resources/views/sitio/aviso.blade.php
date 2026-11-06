@php
  $loc = $aviso->localidad;
  $lugar = $loc->nombre.($loc->comuna->nombre !== $loc->nombre ? ', '.$loc->comuna->nombre : '');
  $titulo = $aviso->titulo.' · '.($aviso->subtipo ?: $categoria['nombre']).' en '.$lugar;
  $jsonld = array_filter([
      '@context' => 'https://schema.org',
      '@type' => $categoria['schema'],
      'name' => $aviso->titulo,
      'description' => $aviso->descripcion_corta,
      'url' => $aviso->url,
      'address' => array_filter([
          '@type' => 'PostalAddress',
          'streetAddress' => $aviso->direccion,
          'addressLocality' => $loc->nombre,
          'addressRegion' => $loc->comuna->region->nombre,
          'addressCountry' => $marca['pais'],
      ]),
      'geo' => ($aviso->lat && ! $aviso->ubicacion_aproximada)
          ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $aviso->lat, 'longitude' => (float) $aviso->lng]
          : null,
  ]);
@endphp
@extends('sitio.layout', ['titulo' => $titulo, 'descripcion' => $aviso->descripcion_corta, 'canonica' => $aviso->url])

@push('jsonld')
<script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('contenido')
<div class="miga">
  <a href="{{ route('inicio') }}">Inicio</a> ›
  <a href="{{ route('listado', [$categoria['slug'], $loc->slug]) }}">{{ $categoria['nombre'] }} en {{ $loc->nombre }}</a>
</div>
<h1>{{ $aviso->titulo }}</h1>
<p style="color:var(--suave);margin-top:0">{{ $aviso->subtipo ?: $categoria['nombre'] }} · {{ $lugar }}, Región de {{ $loc->comuna->region->nombre }}</p>

@php $fotos = array_slice(array_values(array_filter((array) $aviso->fotos)), 0, 10); @endphp
@if (count($fotos))
<div class="galeria">
  @foreach (array_slice($fotos, 0, 5) as $i => $f)
    <div class="media"><img src="{{ asset('storage/'.$f) }}" alt="{{ $aviso->titulo }}{{ $i ? ' · foto '.($i + 1) : '' }}" @if ($i) loading="lazy" @endif></div>
  @endforeach
</div>
@else
@php $variantes = collect(range(0, 11))->shuffle()->take(4); @endphp
<div class="galeria ilus">
  @foreach ($variantes as $v)
    <div class="media">{!! \App\Support\Ilustracion::svg($aviso, '', $v) !!}</div>
  @endforeach
</div>
<p class="sin-fotos">Ilustraciones referenciales. <strong>Aquí podrían ir tus fotos</strong>: si este es tu negocio, actívalo gratis y sube hasta 10.</p>
@endif

@if ($aviso->descripcion)
  <div>{!! nl2br(e($aviso->descripcion)) !!}</div>
@else
  <p>{{ $aviso->descripcion_corta }}</p>
@endif

@if ($aviso->direccion)
  <p><strong>Dirección:</strong> {{ $aviso->direccion }}@if ($aviso->ubicacion_aproximada) <span style="color:var(--suave)">(ubicación aproximada)</span>@endif</p>
@endif

@unless ($aviso->activado)
<div class="aviso-sin">
  <strong>Este aviso aún no ha sido activado por su dueño.</strong>
  <p style="margin:6px 0 0">Los datos provienen de registros públicos y pueden estar desactualizados. ¿Es tu negocio? Actívalo gratis para subir tus fotos, contacto directo y precios.</p>
</div>
@endunless

@if ($cercanos->isNotEmpty())
<h2>Cerca, en {{ $loc->nombre }}</h2>
<div class="grilla">
@foreach ($cercanos as $c)
  <a class="tarjeta con-media" href="{{ route('aviso.ver', [$c->categoria_info['slug'], $loc->slug, $c->slug]) }}">
    @include('sitio._media', ['aviso' => $c])
    <div class="txt">
    <span class="chip">{{ $c->categoria_info['nombre'] }}</span>
    <h3>{{ $c->titulo }}</h3>
    <p>{{ $c->subtipo }}</p>
    </div>
  </a>
@endforeach
</div>
@endif
@endsection
