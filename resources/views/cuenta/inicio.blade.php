@extends('sitio.layout', ['titulo' => 'Mi cuenta · '.$marca['nombre']])
@section('contenido')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
  <h1>Hola, {{ \Illuminate\Support\Str::before($user->name, ' ') }}</h1>
  <form method="post" action="{{ route('cuenta.salir') }}">@csrf<button class="btn sec chico" type="submit">Salir</button></form>
</div>

<h2>Mis avisos</h2>
@if ($avisos->isEmpty())
  <p>Aún no tienes avisos. Si tu negocio ya aparece en el sitio, búscalo y presiona <strong>“¿Es tu negocio?”</strong> para reclamarlo. Si no aparece, créalo.</p>
@else
<div class="grilla">
@foreach ($avisos as $a)
  <div class="tarjeta con-media">
    @include('sitio._media', ['aviso' => $a])
    <div class="txt">
      <span class="estado">{{ \App\Models\Aviso::ESTADOS[$a->estado] ?? $a->estado }}</span>
      <h3 style="margin-top:6px">{{ $a->titulo }}</h3>
      <p>{{ $a->localidad->nombre }} · {{ count((array) $a->fotos) }}/{{ \App\Models\Aviso::MAX_FOTOS }} fotos</p>
      <p style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
        <a class="btn chico" href="{{ route('cuenta.aviso.editar', $a) }}">Editar</a>
        <a class="btn sec chico" href="{{ $a->url }}">Ver</a>
      </p>
    </div>
  </div>
@endforeach
</div>
@endif
<p style="margin-top:18px"><a class="btn" href="{{ route('cuenta.aviso.nuevo') }}">+ Publicar un negocio nuevo</a></p>

@if ($reclamos->isNotEmpty())
<h2>Solicitudes</h2>
@foreach ($reclamos as $r)
  <div class="tarjeta" style="margin-bottom:10px">
    <strong>{{ $r->aviso->titulo }}</strong> · {{ $r->aviso->localidad->nombre }}
    <span class="estado">{{ \App\Models\Reclamo::ESTADOS[$r->estado] }}</span>
    @if ($r->estado === 'rechazado' && $r->motivo_rechazo)<p>{{ $r->motivo_rechazo }}</p>@endif
  </div>
@endforeach
@endif
@endsection
