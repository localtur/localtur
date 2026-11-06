<!doctype html>
<html lang="es">
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
<meta property="og:locale" content="{{ $marca['locale'] }}">
@stack('jsonld')
<style>
:root{--fondo:#faf8f4;--tinta:#1f2a28;--suave:#5d6b68;--linea:#e3ded4;--marca:#0f6b5c;--marca-2:#e8f2ef;--acento:#c8642c;--tarjeta:#fff;--r:12px}
@media (prefers-color-scheme:dark){:root{--fondo:#121615;--tinta:#e8ecea;--suave:#9aa8a4;--linea:#2a3331;--marca:#4fb8a3;--marca-2:#1b2a27;--tarjeta:#1a201f}}
*{box-sizing:border-box}body{margin:0;background:var(--fondo);color:var(--tinta);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
a{color:var(--marca)}.env{max-width:1080px;margin:0 auto;padding:0 16px}
header.sup{border-bottom:1px solid var(--linea);background:var(--tarjeta)}header.sup .env{display:flex;align-items:center;justify-content:space-between;height:60px}
.logo{font-weight:800;font-size:20px;color:var(--tinta);text-decoration:none;letter-spacing:-.02em}.logo b{color:var(--marca)}
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
  <a class="logo" href="{{ route('inicio') }}">{{ $marca['nombre'] }}</a>
  @unless (\App\Support\Marca::indexar())<span class="proto">Prototipo</span>@endunless
</div></header>
<main class="env">
@yield('contenido')
</main>
<footer><div class="env">{{ $marca['nombre'] }} · {{ $marca['eslogan'] }}</div></footer>
</body>
</html>
