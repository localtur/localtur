{{-- Pin de mapa relleno con la bandera del país. $bandera: cl, pe, co o latam. $alto en px. --}}
@php $id = 'pin'.($bandera ?? 'latam').substr(md5(uniqid('', true)), 0, 6); $alto = $alto ?? 34; @endphp
<svg width="{{ round($alto * 40 / 52) }}" height="{{ $alto }}" viewBox="0 0 40 52" aria-hidden="true" class="pin">
  <defs><clipPath id="{{ $id }}"><path d="M20 1C9.5 1 1 9.5 1 20c0 13.6 19 31 19 31s19-17.4 19-31C39 9.5 30.5 1 20 1Z"/></clipPath></defs>
  <g clip-path="url(#{{ $id }})">
  @switch($bandera ?? 'latam')
    @case('cl')
      <rect width="40" height="52" fill="#d52b1e"/><rect width="40" height="21" fill="#fff"/><rect width="19" height="21" fill="#0039a6"/>
      <path d="M9.5 5.2l1.6 4.9h5.1l-4.1 3 1.6 4.9-4.2-3-4.2 3 1.6-4.9-4.1-3h5.1z" fill="#fff"/>
      @break
    @case('pe')
      <rect width="40" height="52" fill="#d91023"/><rect x="13.4" width="13.2" height="52" fill="#fff"/>
      @break
    @case('co')
      <rect width="40" height="52" fill="#ce1126"/><rect width="40" height="38" fill="#003893"/><rect width="40" height="25" fill="#fcd116"/>
      @break
    @case('mx')
      <rect width="40" height="52" fill="#ce1126"/><rect width="26.7" height="52" fill="#fff"/><rect width="13.3" height="52" fill="#006847"/>
      <circle cx="20" cy="20" r="3.6" fill="#8c6a2f"/><path d="M16.6 22.6q3.4 2.6 6.8 0" stroke="#4f7a3a" stroke-width="1.2" fill="none"/>
      @break
    @default
      <rect width="40" height="52" fill="#0f6b5c"/><circle cx="20" cy="20" r="7" fill="#fff"/>
  @endswitch
  </g>
  <path d="M20 1C9.5 1 1 9.5 1 20c0 13.6 19 31 19 31s19-17.4 19-31C39 9.5 30.5 1 20 1Z" fill="none" stroke="rgba(0,0,0,.18)" stroke-width="1.5"/>
</svg>
