@php $fotos = array_values(array_filter((array) $aviso->fotos)); @endphp
<div class="media">
@if (count($fotos))
  <img src="{{ asset('storage/'.$fotos[0]) }}" alt="{{ $aviso->titulo }}" loading="lazy">
@else
  {!! \App\Support\Ilustracion::svg($aviso) !!}
@endif
</div>
