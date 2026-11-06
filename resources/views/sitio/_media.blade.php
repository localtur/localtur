@php $fotos = array_values(array_filter((array) $aviso->fotos)); $ref = count($fotos) ? null : \App\Support\Ilustracion::imagenDe($aviso); @endphp
<div class="media {{ $ref ? 'ref' : '' }}">
@if (count($fotos))
  <img src="{{ asset('storage/'.$fotos[0]) }}" alt="{{ $aviso->titulo }}" loading="lazy">
@elseif ($ref)
  <img src="{{ $ref }}" alt="Ilustración referencial" loading="lazy">
@else
  {!! \App\Support\Ilustracion::svg($aviso) !!}
@endif
</div>
