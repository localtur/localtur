@if (\App\Support\Pais::multipais())
  <a class="logo" href="{{ isset($pais) ? \App\Support\Pais::ruta('inicio') : route('raiz') }}" aria-label="LocalTur.com{{ isset($pais) ? ' '.$pais['nombre'] : '' }}">
    @include('sitio._pin', ['bandera' => $pais['bandera'] ?? 'latam', 'alto' => 34])
    <span>Local<b>Tur</b><small>.com</small></span>
  </a>
@else
  <a class="logo" href="{{ \App\Support\Pais::ruta('inicio') }}">{{ $marca['nombre'] }}</a>
@endif
