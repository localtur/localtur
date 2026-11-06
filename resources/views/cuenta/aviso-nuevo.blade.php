@extends('sitio.layout', ['titulo' => 'Publicar un negocio · '.$marca['nombre']])
@section('contenido')
<h1>Publica tu negocio en {{ $pais['nombre'] }}</h1>
<p style="color:var(--suave);max-width:620px">Primero lo básico. En el paso siguiente agregas descripción, contacto y fotos.</p>
<form class="form ancho" method="post" action="{{ route('cuenta.aviso.crear') }}">
  @csrf
  <div class="dos">
    <div>
      <label for="categoria">Categoría</label>
      <select id="categoria" name="categoria" required>
        @foreach ($categorias as $k => $c)<option value="{{ $k }}" @selected(old('categoria') === $k)>{{ $c['nombre'] }}</option>@endforeach
      </select>
    </div>
    <div>
      <label for="subtipo">Tipo</label>
      <input type="text" id="subtipo" name="subtipo" value="{{ old('subtipo') }}" placeholder="Cabañas, hostal, restaurante, kayak…">
    </div>
  </div>
  <label for="titulo">Nombre del negocio</label>
  <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" maxlength="60" required>
  <div class="dos">
    <div>
      <label for="region">{{ $pais['division']['region'] }}</label>
      <input type="text" id="region" name="region" value="{{ old('region') }}" list="lista-regiones" required>
      <datalist id="lista-regiones">@foreach ($regiones as $r)<option value="{{ $r }}">@endforeach</datalist>
    </div>
    <div>
      <label for="comuna">{{ $pais['division']['comuna'] }}</label>
      <input type="text" id="comuna" name="comuna" value="{{ old('comuna') }}" list="lista-comunas" required>
      <datalist id="lista-comunas">@foreach ($comunas as $c)<option value="{{ $c }}">@endforeach</datalist>
    </div>
  </div>
  <label for="localidad">{{ $pais['division']['localidad'] }} <span style="font-weight:400;color:var(--suave)">(si es distinta de la {{ mb_strtolower($pais['division']['comuna']) }})</span></label>
  <input type="text" id="localidad" name="localidad" value="{{ old('localidad') }}">
  <label for="direccion">Dirección</label>
  <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}">
  <label class="check"><input type="checkbox" name="acepto" value="1"> El negocio es mío o estoy autorizado para publicarlo.</label>
  <p style="margin-top:20px"><button class="btn" type="submit">Continuar</button></p>
</form>
@endsection
