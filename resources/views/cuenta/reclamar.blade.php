@extends('sitio.layout', ['titulo' => '¿Es tu negocio? · '.$aviso->titulo])
@section('contenido')
<h1>¿Es tu negocio?</h1>
<p style="color:var(--suave)"><strong>{{ $aviso->titulo }}</strong> · {{ $aviso->subtipo }} · {{ $aviso->localidad->nombre }}, {{ $aviso->localidad->comuna->region->nombre }}</p>

@if ($aviso->user_id)
  <div class="alerta error">Este aviso ya tiene un responsable. Si crees que es un error, escríbenos.</div>
@elseif ($previo && $previo->estado === 'pendiente')
  <div class="alerta ok">Ya enviaste una solicitud para este aviso. La estamos revisando.</div>
@else
<p style="max-width:620px">Al activarlo podrás agregar tus fotos (hasta 10), descripción, precios y tus datos de contacto directo. La activación es gratis. Revisamos cada solicitud para proteger a los dueños reales.</p>
<form class="form" method="post" action="{{ route('cuenta.reclamar.enviar', $aviso) }}">
  @csrf
  <label for="relacion">¿Cuál es tu relación con el negocio?</label>
  <select id="relacion" name="relacion" required>
    @foreach (\App\Models\Reclamo::RELACIONES as $k => $v)<option value="{{ $k }}" @selected(old('relacion') === $k)>{{ $v }}</option>@endforeach
  </select>
  <label for="telefono">Teléfono de contacto</label>
  <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', auth()->user()->telefono) }}" required placeholder="{{ $pais['prefijo'] }} …">
  <div class="ayuda">Puede que te llamemos para confirmar. No se publica.</div>
  <label for="mensaje">Algo que nos ayude a verificar <span style="font-weight:400;color:var(--suave)">(opcional)</span></label>
  <textarea id="mensaje" name="mensaje" placeholder="Por ejemplo: tu web, Instagram o la razón social">{{ old('mensaje') }}</textarea>
  <label class="check"><input type="checkbox" name="acepto" value="1"> Declaro que soy responsable de este negocio o estoy autorizado para administrarlo, y que las fotos que suba son mías o tengo permiso para publicarlas.</label>
  <p style="margin-top:20px"><button class="btn" type="submit">Enviar solicitud</button></p>
</form>
@endif
@endsection
