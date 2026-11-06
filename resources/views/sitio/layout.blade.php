<!doctype html>
<html lang="{{ isset($pais) ? str_replace('_', '-', $pais['locale']) : 'es' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $titulo ?? $marca['nombre'] }}</title>
<meta name="description" content="{{ $descripcion ?? $marca['eslogan'] }}">
@unless (\App\Support\Marca::indexar())
<meta name="robots" content="noindex, nofollow">
@endunless
@isset($canonica)<link rel="canonical" href="{{ $canonica }}">@endisset
<meta property="og:title" content="{{ $titulo ?? $marca['nombre'] }}">
<meta property="og:description" content="{{ $descripcion ?? $marca['eslogan'] }}">
<meta property="og:locale" content="{{ $pais['locale'] ?? $marca['locale'] }}">
<meta property="og:site_name" content="{{ $marca['nombre'] }}">
@stack('jsonld')
<style>
:root{--fondo:#faf8f4;--tinta:#1f2a28;--suave:#5d6b68;--linea:#e3ded4;--marca:#0f6b5c;--marca-2:#e8f2ef;--acento:#c8642c;--tarjeta:#fff;--r:12px}
@media (prefers-color-scheme:dark){:root{--fondo:#121615;--tinta:#e8ecea;--suave:#9aa8a4;--linea:#2a3331;--marca:#4fb8a3;--marca-2:#1b2a27;--tarjeta:#1a201f}}
@isset($pais)
:root{--marca:{{ $pais['colores']['principal'] }};--acento:{{ $pais['colores']['secundario'] }};--marca-2:{{ $pais['colores']['suave'] }};--hero:{{ $pais['colores']['hero'] ?? $pais['colores']['principal'] }};--hero-2:{{ $pais['colores']['hero_2'] ?? $pais['colores']['principal'] }};--hero-tinta:{{ $pais['colores']['tinta_hero'] }};--franja:{{ $pais['colores']['franja'] ?? 'transparent' }}}
@media (prefers-color-scheme:dark){:root{--marca:color-mix(in srgb,{{ $pais['colores']['principal'] }} 55%,#fff);--marca-2:color-mix(in srgb,{{ $pais['colores']['principal'] }} 22%,#121615)}}
@endisset
*{box-sizing:border-box}body{margin:0;background:var(--fondo);color:var(--tinta);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
a{color:var(--marca)}.env{max-width:1080px;margin:0 auto;padding:0 16px}
header.sup{border-bottom:1px solid var(--linea);background:var(--tarjeta)}header.sup .env{display:flex;align-items:center;justify-content:space-between;height:60px}
.logo{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:21px;color:var(--tinta);text-decoration:none;letter-spacing:-.02em}.logo b{color:var(--marca)}.logo small{font-size:.62em;font-weight:600;color:var(--suave);letter-spacing:0}
.sup .der{display:flex;align-items:center;gap:12px}.banderas{display:flex;gap:6px}.banderas a{display:flex;opacity:.55}.banderas a.si,.banderas a:hover{opacity:1}
.hero{background:linear-gradient(120deg,var(--hero),var(--hero-2));color:var(--hero-tinta);border-radius:18px;padding:36px 180px 44px 28px;margin:22px 0 8px;position:relative;overflow:hidden}
.hero::after{content:"";position:absolute;left:0;right:0;bottom:0;height:10px;background:var(--franja,transparent)}
@media (max-width:640px){.hero{padding:28px 20px 36px}.hero .pin{display:none}}
.hero h1{margin:0 0 8px}.hero p{margin:0;max-width:640px;opacity:.92}.hero .pin{position:absolute;right:36px;top:50%;transform:translateY(-50%);filter:drop-shadow(0 6px 10px rgba(0,0,0,.25))}
.chips{display:flex;flex-wrap:wrap;gap:8px}.chips span{padding:6px 12px;border-radius:99px;background:var(--marca-2);color:var(--marca);font-size:14px}
.cruce{margin:22px 0;padding:14px 16px;border-radius:var(--r);border:1px solid var(--linea);background:var(--tarjeta)}
.proto{font-size:12px;background:var(--acento);color:#fff;padding:3px 8px;border-radius:99px}
h1{font-size:clamp(26px,4vw,38px);line-height:1.15;letter-spacing:-.02em;margin:28px 0 8px}h2{font-size:20px;margin:28px 0 12px}
.miga{font-size:14px;color:var(--suave);margin-top:18px}.miga a{color:var(--suave)}
.grilla{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.tarjeta{background:var(--tarjeta);border:1px solid var(--linea);border-radius:var(--r);padding:16px;text-decoration:none;color:inherit;display:block}
.tarjeta:hover{border-color:var(--marca)}.tarjeta h3{margin:0 0 4px;font-size:16px}.tarjeta p{margin:0;color:var(--suave);font-size:14px}
.chip{display:inline-block;font-size:12px;padding:2px 8px;border-radius:99px;background:var(--marca-2);color:var(--marca);margin-bottom:8px}
.chip.gris{background:var(--linea);color:var(--suave)}
.aviso-sin{border:1px dashed var(--linea);border-radius:var(--r);padding:16px;background:var(--marca-2);margin:20px 0}
.cats{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0}.cats a{padding:6px 12px;border:1px solid var(--linea);border-radius:99px;text-decoration:none;color:var(--tinta);font-size:14px;background:var(--tarjeta)}
footer{margin:48px 0 0;padding:24px 0;border-top:1px solid var(--linea);color:var(--suave);font-size:14px}
nav.pag{margin:20px 0}nav.pag svg{width:16px}
.media{position:relative;border-radius:var(--r);overflow:hidden;background:var(--linea);aspect-ratio:16/9}
.media svg,.media img{display:block;width:100%;height:100%;object-fit:cover}
.media.ref{background:#fff}.media.ref img{object-fit:contain}
.media .nota{position:absolute;left:10px;bottom:10px;font-size:12px;background:rgba(0,0,0,.55);color:#fff;padding:3px 8px;border-radius:99px}
.galeria{display:grid;grid-template-columns:2fr 1fr 1fr;gap:6px;margin:18px 0 0}.galeria .media:first-child{grid-row:span 2;aspect-ratio:auto}
.galeria.ilus{grid-template-columns:2fr 1fr}.galeria.ilus .media:first-child{grid-row:span 3}
@media (max-width:640px){.galeria,.galeria.ilus{grid-template-columns:1fr 1fr 1fr}.galeria .media:first-child,.galeria.ilus .media:first-child{grid-column:span 3;grid-row:auto;aspect-ratio:16/9}}
.sin-fotos{margin:8px 0 20px;font-size:14px;color:var(--suave);padding:10px 14px;border:1px dashed var(--linea);border-radius:var(--r)}
.tarjeta.con-media{padding:0;overflow:hidden}.tarjeta.con-media .media{border-radius:0;aspect-ratio:16/10}.tarjeta.con-media .txt{padding:14px 16px}
</style>
</head>
<body>
<header class="sup"><div class="env">
  @include('sitio._logo')
  <div class="der">
    @if (\App\Support\Pais::multipais())
    <nav class="banderas" aria-label="País">
      @foreach (\App\Support\Pais::todos() as $p)
        <a href="{{ route('inicio', ['pais' => $p['codigo']]) }}" class="{{ isset($pais) && $pais['codigo'] === $p['codigo'] ? 'si' : '' }}" title="{{ $p['nombre'] }}">@include('sitio._pin', ['bandera' => $p['bandera'], 'alto' => 22])</a>
      @endforeach
    </nav>
    @endif
    @unless (\App\Support\Marca::indexar())<span class="proto">Prototipo</span>@endunless
  </div>
</div></header>
<main class="env">
@yield('contenido')
</main>
<footer><div class="env">{{ $marca['nombre'] }}{{ isset($pais) && \App\Support\Pais::multipais() ? ' '.$pais['nombre'] : '' }} · {{ $pais['eslogan'] ?? $marca['eslogan'] }}</div></footer>
</body>
</html>
