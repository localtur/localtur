@extends('sitio.layout', ['titulo' => 'Ingresar · '.$marca['nombre']])
@section('contenido')
<h1>Ingresar</h1>
<form class="form" method="post" action="{{ route('ingresar') }}">
  @csrf
  <label for="email">Correo</label>
  <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
  <label for="password">Contraseña</label>
  <input type="password" id="password" name="password" required autocomplete="current-password">
  <label class="check"><input type="checkbox" name="recordar" value="1"> Mantener la sesión abierta</label>
  <p style="margin-top:20px"><button class="btn" type="submit">Ingresar</button></p>
  <p>¿No tienes cuenta? <a href="{{ route('registro') }}">Crea una gratis</a></p>
</form>
@endsection
