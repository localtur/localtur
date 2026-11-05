@extends('sitio.layout', ['titulo' => $marca['nombre'].' · '.$marca['eslogan']])

@section('contenido')
<h1>{{ $marca['eslogan'] }}</h1>
<p style="color:var(--suave);max-width:640px">Alojamientos, lugares para comer, actividades, comercio y servicios. Contacto directo con quien atiende, sin intermediarios.</p>

<h2>Destinos</h2>
@if ($localidades->isEmpty())
  <p>Todavía no hay avisos cargados.</p>
@else
<div class="grilla">
@foreach ($localidades as $l)
  <div class="tarjeta">
    <h3>{{ $l->nombre }}</h3>
    <p>{{ $l->comuna->nombre !== $l->nombre ? $l->comuna->nombre.', ' : '' }}{{ $l->comuna->region->nombre }} · {{ $l->avisos_count }} avisos</p>
    <div class="cats" style="margin-bottom:0">
    @foreach ($categorias as $c)
      <a href="{{ route('listado', [$c['slug'], $l->slug]) }}">{{ $c['nombre'] }}</a>
    @endforeach
    </div>
  </div>
@endforeach
</div>
@endif
@endsection
