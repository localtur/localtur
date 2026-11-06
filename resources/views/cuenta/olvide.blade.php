@extends('sitio.layout', ['titulo' => 'Recuperar contraseña · '.$marca['nombre']])
@section('contenido')
<h1>¿Olvidaste tu contraseña?</h1>
<p style="color:var(--suave);max-width:560px">Escribe el correo de tu cuenta y te enviaremos un enlace para elegir una nueva.</p>
<form class="form" method="post" action="{{ route('password.email') }}">
  @csrf
  <label for="email">Correo</label>
  <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
  <p style="margin-top:20px"><button class="btn" type="submit">Enviar enlace</button></p>
  <p><a href="{{ route('login') }}">Volver a ingresar</a></p>
</form>
@endsection
