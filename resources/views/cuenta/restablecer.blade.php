@extends('sitio.layout', ['titulo' => 'Nueva contraseña · '.$marca['nombre']])
@section('contenido')
<h1>Elige una contraseña nueva</h1>
<form class="form" method="post" action="{{ route('password.update') }}">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <label for="email">Correo</label>
  <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
  <div class="dos">
    <div><label for="password">Contraseña nueva</label><input type="password" id="password" name="password" required autocomplete="new-password"><div class="ayuda">Al menos 10 caracteres.</div></div>
    <div><label for="password_confirmation">Repite la contraseña</label><input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div>
  </div>
  <p style="margin-top:20px"><button class="btn" type="submit">Guardar contraseña</button></p>
</form>
@endsection
