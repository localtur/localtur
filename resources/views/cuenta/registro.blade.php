@extends('sitio.layout', ['titulo' => 'Crear cuenta · '.$marca['nombre']])
@section('contenido')
<h1>Crea tu cuenta</h1>
<p style="color:var(--suave);max-width:560px">Para publicar tu negocio, reclamar un aviso que ya existe o guardar tus favoritos. Es gratis.</p>
<form class="form" method="post" action="{{ route('registrar') }}">
  @csrf
  <label for="name">Nombre</label>
  <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
  <label for="email">Correo</label>
  <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
  <label for="telefono">Teléfono <span style="font-weight:400;color:var(--suave)">(opcional)</span></label>
  <input type="tel" id="telefono" name="telefono" value="{{ old('telefono') }}" placeholder="{{ $pais['prefijo'] }} …" autocomplete="tel">
  <div class="dos">
    <div><label for="password">Contraseña</label><input type="password" id="password" name="password" required autocomplete="new-password"><div class="ayuda">Al menos 10 caracteres.</div></div>
    <div><label for="password_confirmation">Repite la contraseña</label><input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div>
  </div>
  <label class="check"><input type="checkbox" name="acepto" value="1" @checked(old('acepto'))> Acepto los términos de uso y la política de privacidad de {{ $marca['nombre'] }}.</label>
  <p style="margin-top:20px"><button class="btn" type="submit">Crear cuenta</button></p>
  <p>¿Ya tienes cuenta? <a href="{{ route('login') }}">Ingresa</a></p>
</form>
@endsection
