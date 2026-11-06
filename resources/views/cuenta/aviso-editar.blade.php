@extends('sitio.layout', ['titulo' => 'Editar · '.$aviso->titulo])
@section('contenido')
@php $fotos = array_values(array_filter((array) $aviso->fotos)); @endphp
<div class="miga"><a href="{{ route('cuenta.inicio') }}">Mi cuenta</a> › Editar aviso</div>
<h1>{{ $aviso->titulo }}</h1>
<p style="margin-top:0"><span class="estado">{{ \App\Models\Aviso::ESTADOS[$aviso->estado] ?? $aviso->estado }}</span> · <a href="{{ $aviso->url }}">Ver aviso publicado</a></p>

<form class="form ancho" method="post" action="{{ route('cuenta.aviso.actualizar', $aviso) }}" enctype="multipart/form-data">
  @csrf @method('put')

  <h2>Fotos <span style="font-weight:400;color:var(--suave);font-size:15px">{{ count($fotos) }}/{{ $max }}</span></h2>
  @if ($fotos)
  <div class="fotos-grid">
    @foreach ($fotos as $i => $f)
    <figure>
      <img src="{{ asset('storage/'.$f) }}" alt="Foto {{ $i + 1 }}">
      <figcaption>
        <label class="check" style="margin:0"><input type="radio" name="portada" value="{{ $f }}" @checked($i === 0)> Portada</label>
        <label class="check" style="margin:0"><input type="checkbox" name="borrar[]" value="{{ $f }}"> Quitar</label>
      </figcaption>
    </figure>
    @endforeach
  </div>
  @else
  <p style="color:var(--suave)">Sin fotos: tu aviso muestra ilustraciones referenciales. Las fotos reales generan mucho más contacto.</p>
  @endif
  @if (count($fotos) < $max)
  <label for="fotos_nuevas">Agregar fotos</label>
  <input type="file" id="fotos_nuevas" name="fotos_nuevas[]" accept="image/jpeg,image/png,image/webp" multiple>
  <div class="ayuda">Hasta {{ $max - count($fotos) }} más · JPG, PNG o WebP · máx. 8 MB cada una. Las achicamos automáticamente.</div>
  @endif

  <h2>Datos del aviso</h2>
  <div class="dos">
    <div><label for="titulo">Nombre</label><input type="text" id="titulo" name="titulo" value="{{ old('titulo', $aviso->titulo) }}" maxlength="60" required></div>
    <div><label for="subtipo">Tipo</label><input type="text" id="subtipo" name="subtipo" value="{{ old('subtipo', $aviso->subtipo) }}"></div>
  </div>
  <label for="descripcion_corta">Resumen <span style="font-weight:400;color:var(--suave)">(aparece en Google; hasta 160 caracteres)</span></label>
  <input type="text" id="descripcion_corta" name="descripcion_corta" value="{{ old('descripcion_corta', $aviso->descripcion_corta) }}" maxlength="160">
  <label for="descripcion">Descripción</label>
  <textarea id="descripcion" name="descripcion" rows="8">{{ old('descripcion', $aviso->descripcion) }}</textarea>
  <label for="direccion">Dirección</label>
  <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $aviso->direccion) }}">

  <h2>Contacto directo</h2>
  <p class="ayuda" style="margin-top:-6px">Se muestra en tu aviso para que los viajeros te contacten sin intermediarios.</p>
  <div class="dos">
    <div><label for="contacto_whatsapp">WhatsApp</label><input type="tel" id="contacto_whatsapp" name="contacto_whatsapp" value="{{ old('contacto_whatsapp', $aviso->contacto_whatsapp) }}" placeholder="{{ $pais['prefijo'] }} 9 …"></div>
    <div><label for="contacto_telefono">Teléfono</label><input type="tel" id="contacto_telefono" name="contacto_telefono" value="{{ old('contacto_telefono', $aviso->contacto_telefono) }}"></div>
    <div><label for="contacto_correo">Correo</label><input type="email" id="contacto_correo" name="contacto_correo" value="{{ old('contacto_correo', $aviso->contacto_correo) }}"></div>
    <div><label for="contacto_web">Web o reservas</label><input type="url" id="contacto_web" name="contacto_web" value="{{ old('contacto_web', $aviso->contacto_web) }}" placeholder="https://"></div>
  </div>

  <p style="margin-top:24px"><button class="btn" type="submit">Guardar cambios</button></p>
</form>
@endsection
