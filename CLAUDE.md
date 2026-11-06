# localtur — notas para retomar el trabajo

Prototipo del sistema nuevo de **LocalTur.com** (América Latina) y **alojarse.cl** (Chile), mismo código.
Estado al 2026-11-06: pausado para trabajar en alojarse.cl. Respuestas al usuario siempre en español.

## Stack y despliegue
- Laravel 12 + Filament 3.3 + PHP 8.3 + MySQL (Hostinger Cloud Startup, cuenta u223144618).
- En el entorno de Claude **no hay acceso a Packagist**: composer corre solo en GitHub Actions.
- `.github/workflows/ci.yml`: en cada push a `main` instala, migra, importa la muestra, corre `php artisan test`,
  escribe `informe.txt` en la rama **`ci-report`** y, si todo pasa, publica la rama **`deploy`** (código + vendor/).
  Leer el resultado: `git fetch -f origin ci-report:refs/remotes/origin/ci-report && git show origin/ci-report:informe.txt`.
- Hostinger despliega `deploy` automáticamente en `~/domains/staging.localtur.com/public_html` (raíz con `.htaccess` → `public/`).
- Después de un despliegue con migraciones, el usuario corre por SSH (`ssh -p 65002 u223144618@82.197.85.205`, PowerShell permite pegar):
  `cd ~/domains/staging.localtur.com/public_html && php artisan migrate --force`. El CLI usa PHP 8.3 vía alias en `~/.bashrc`.
- `.env` del servidor lo edita el usuario (nunca pedir contraseñas por chat). Respaldo en `~/staging-localtur.env`.
- Correo: SMTP Hostinger `no.responder@localtur.com` (smtps 465). Prueba: `php artisan localtur:probar-correo correo@x`.
- Ver en local sin composer: `git worktree add <dir> origin/deploy` (trae vendor/, sin dev deps) y copiar el código encima.

## Marcas y países
- `config/marca.php`: `localtur` (multipaís, hosts localtur.com y staging.localtur.com) y `alojarse` (`pais_fijo` cl,
  hosts alojarse.cl y prueba-alojarse.localtur.com — subdominio aún por crear). Alias `www.` redirigen 301.
- Reparto de Chile: localtur `/cl` = solo región `metropolitana-de-santiago`; alojarse = todas menos esa. Otros países: todo.
- `config/paises.php`: cl, pe, co, mx con colores, moneda, prefijo, id fiscal, nombres de división, destinos. Pagos: solo Mercado Pago y PayPal.
- URLs: alojarse `/{categoria}/{localidad}/{aviso}`; localtur `/{pais}/{categoria}/{localidad}/{aviso}`; `/` en localtur = elegir país.
- Generar enlaces siempre con `App\Support\Pais::ruta()` (o `rutaParaRegion()` cuando el aviso puede ser de otra marca).
- `ResolverPais` fija marca/país por petición y recuerda el último país en sesión (páginas de cuenta).

## Datos
- Tablas: paises → regiones → comunas → localidades → avisos (+ `avisos_privados` con RUT/teléfonos del registro, nunca públicos).
- Semilla SERNATUR (5.002 filas, datos ~2012–2017): `php artisan localtur:importar-semilla [--comuna=] [--simular]`,
  CSV en `storage/app/private/` (no van al repo: datos personales). Idempotente; no pisa avisos activados.
- Fotos: máx. 10 por aviso, disco `public`, `App\Support\Fotos` (≤1600 px, WebP). Sin fotos: ilustraciones del usuario en
  `public/img/referenciales/*.webp` (4 al azar en la ficha) y fallback SVG `App\Support\Ilustracion`.

## Cuentas
- `/registro`, `/ingresar`, `/olvide-mi-clave`, `/cuenta` (mis avisos), `/cuenta/reclamar/{aviso}` ("¿Es tu negocio?"),
  `/cuenta/avisos/nuevo`, `/cuenta/avisos/{aviso}/editar`. Panel `/admin` solo rol admin/moderador (`php artisan localtur:admin email`).
- Reclamos se aprueban en Filament → Solicitudes; correos `ReclamoRecibido` (a `MAIL_EQUIPO`) y `ReclamoResuelto`.

## Pendiente
1. Planes de pago (Mercado Pago y PayPal): tramos por categoría/tamaño, descuentos fundador/comuna pequeña, aviso básico permanente.
2. Directorio de Administradores, alianzas, consulta múltiple, gastos y resultados, asistente IA (diseños en el Canvas del proyecto).
3. Migrar contenido de Base44 (usuarios, fotos, cuentas pagadas, blog, avisos) y pasar alojarse.cl a este sistema.
4. Hacer privado el repositorio GitHub.
