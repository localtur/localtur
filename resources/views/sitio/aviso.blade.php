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
      'telephone' => $aviso->activado ? ($aviso->contacto_telefono ?: $aviso->contacto_whatsapp) : null,
      'address' => array_filter([
          '@type' => 'PostalAddress',
          'streetAddress' => $aviso->direccion,
          'addressLocality' => $loc->nombre,
          'addressRegion' => $loc->comuna->region->nombre,
          'addressCountry' => strtoupper($pais['codigo']),
      ]),
      'geo' => ($aviso->lat && ! $aviso->ubicacion_aproximada)
          ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $aviso->lat, 'longitude' => (float) $aviso->lng]
          : null,
  ]);
@endphp
@extends('sitio.layout', ['titulo' => $titulo, 'descripcion' => $aviso->descripcion_corta])

@push('jsonld')
<script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('contenido')
<div class="miga">
  <a href="{{ \App\Support\Pais::ruta('inicio') }}">Inicio</a> ›
  <a href="{{ \App\Support\Pais::ruta('listado', [$categoria['slug'], $loc->slug]) }}">{{ $categoria['nombre'] }} en {{ $loc->nombre }}</a>
</div>
<h1>{{ $aviso->titulo }}</h1>
<p style="color:var(--suave);margin-top:0">{{ $aviso->subtipo ?: $categoria['nombre'] }} · {{ $lugar }}, {{ $pais['division']['region'] }} {{ str_starts_with($loc->comuna->region->nombre, 'Metropolitana') ? '' : 'de ' }}{{ $loc->comuna->region->nombre }}</p>

@php $fotos = array_slice(array_values(array_filter((array) $aviso->fotos)), 0, 10); @endphp
@if (count($fotos))
<div class="galeria">
  @foreach (array_slice($fotos, 0, 5) as $i => $f)
    <div class="media"><img src="{{ asset('storage/'.$f) }}" alt="{{ $aviso->titulo }}{{ $i ? ' · foto '.($i + 1) : '' }}" @if ($i) loading="lazy" @endif></div>
  @endforeach
</div>
@else
@php
  $refs = collect(\App\Support\Ilustracion::imagenes($aviso->categoria))->shuffle()->take(4);
  $variantes = $refs->isEmpty() ? collect(range(0, 11))->shuffle()->take(4) : collect();
@endphp
<div class="galeria ilus">
  @foreach ($refs as $i => $url)
    <div class="media ref"><img src="{{ $url }}" alt="Ilustración referencial" @if ($i) loading="lazy" @endif></div>
  @endforeach
  @foreach ($variantes as $v)
    <div class="media">{!! \App\Support\Ilustracion::svg($aviso, '', $v) !!}</div>
  @endforeach
</div>
@if ($aviso->activado)
<p class="sin-fotos">Ilustraciones referenciales: este negocio aún no ha subido fotos.</p>
@else
<p class="sin-fotos">Ilustraciones referenciales. <strong>Aquí podrían ir tus fotos</strong>: si este es tu negocio, <a href="{{ route('cuenta.reclamar', $aviso) }}" rel="nofollow">actívalo gratis</a> y sube hasta 10.</p>
@endif
@endif

@if ($aviso->descripcion)
  <div>{!! nl2br(e($aviso->descripcion)) !!}</div>
@else
  <p>{{ $aviso->descripcion_corta }}</p>
@endif

@if ($aviso->direccion)
  <p><strong>Dirección:</strong> {{ $aviso->direccion }}@if ($aviso->ubicacion_aproximada) <span style="color:var(--suave)">(ubicación aproximada)</span>@endif</p>
@endif

@if ($aviso->activado)
  @php $wa = preg_replace('/\D+/', '', (string) $aviso->contacto_whatsapp); @endphp
  @if ($wa || $aviso->contacto_telefono || $aviso->contacto_correo || $aviso->contacto_web)
  <h2>Contacto directo</h2>
  <div class="contacto">
    @if ($wa)<a class="btn" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hola, vi su aviso "'.$aviso->titulo.'" en '.$marca['nombre']) }}" rel="nofollow">WhatsApp</a>@endif
    @if ($aviso->contacto_telefono)<a class="btn sec" href="tel:{{ preg_replace('/[^\d+]/', '', $aviso->contacto_telefono) }}" rel="nofollow">Llamar</a>@endif
    @if ($aviso->contacto_correo)<a class="btn sec" href="mailto:{{ $aviso->contacto_correo }}" rel="nofollow">Correo</a>@endif
    @if ($aviso->contacto_web)<a class="btn sec" href="{{ $aviso->contacto_web }}" rel="nofollow noopener" target="_blank">Web / reservas</a>@endif
  </div>
  <p class="ayuda" style="font-size:13px;color:var(--suave)">El trato es directo con {{ $aviso->titulo }}. {{ $marca['nombre'] }} no cobra comisiones ni intermedia en el pago.</p>
  @endif
@else
<div class="aviso-sin">
  <strong>Este aviso aún no ha sido activado por su dueño.</strong>
  <p style="margin:6px 0 12px">Los datos provienen de registros públicos y pueden estar desactualizados. ¿Es tu negocio? Actívalo gratis para subir tus fotos, contacto directo y precios.</p>
  <a class="btn" href="{{ route('cuenta.reclamar', $aviso) }}" rel="nofollow">¿Es tu negocio? Actívalo gratis</a>
</div>
@endif

@if ($cercanos->isNotEmpty())
<h2>Cerca, en {{ $loc->nombre }}</h2>
<div class="grilla">
@foreach ($cercanos as $c)
  <a class="tarjeta con-media" href="{{ \App\Support\Pais::ruta('aviso.ver', [$c->categoria_info['slug'], $loc->slug, $c->slug]) }}">
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
