@extends('sitio.layout', ['titulo' => 'LocalTur.com · '.$marca['eslogan'], 'descripcion' => $marca['eslogan']])

@section('contenido')
<section class="hero" style="--hero:#0f6b5c;--hero-2:#0b5247;--hero-tinta:#fff;--franja:linear-gradient(90deg,#0039a6 0 25%,#d91023 25% 50%,#fcd116 50% 75%,#006847 75%)">
  <h1>{{ $marca['eslogan'] }}</h1>
  <p>Elige tu destino. Cada país con sus alojamientos, su comida, sus actividades y su gente.</p>
  @include('sitio._pin', ['bandera' => 'latam', 'alto' => 120])
</section>
<div class="grilla paises" style="margin-top:18px">
@foreach ($paises as $p)
  <a class="tarjeta pais" href="{{ route('inicio', ['pais' => $p['codigo']]) }}" style="border-top:5px solid {{ $p['colores']['principal'] }}">
    @include('sitio._pin', ['bandera' => $p['bandera'], 'alto' => 56])
    <h3 style="font-size:22px;margin-top:10px">{{ $p['nombre'] }}</h3>
    <p>{{ $p['activo'] ? implode(' · ', array_slice($p['destinos'], 0, 3)) : 'Próximamente' }}</p>
  </a>
@endforeach
</div>
@endsection
