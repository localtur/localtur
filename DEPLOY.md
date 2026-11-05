# Puesta en marcha en Hostinger (localtur.com)

El servidor nunca corre `composer`: GitHub Actions prueba cada cambio y publica la rama **`deploy`**
(código + `vendor/`). Hostinger solo descarga esa rama.

## Una sola vez, en hPanel

1. **PHP**: Avanzado → Configuración de PHP → versión **8.3**.
2. **Base de datos**: Bases de datos → MySQL → crear base y usuario. Anota nombre, usuario y host (la contraseña no la compartas con nadie).
3. **GIT**: Avanzado → GIT
   - Repositorio: `https://github.com/localtur/localtur.git` (si lo haces privado, Hostinger te da una clave de despliegue para agregar en GitHub → Settings → Deploy keys).
   - Rama: **`deploy`**
   - Directorio: vacío (= `public_html`). Debe estar vacío antes del primer despliegue.
   - Activa **despliegue automático** y copia la URL del webhook en GitHub → Settings → Webhooks.
4. **Terminal SSH** (Avanzado → Acceso SSH), dentro de `public_html`:

```bash
php -v                         # debe decir 8.3; si no, usa /opt/alt/php83/usr/bin/php
cp .env.example .env
nano .env                      # ver abajo
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan localtur:admin tu@correo.cl     # te pide la contraseña en pantalla
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

En `.env` cambia estas líneas (la contraseña la escribes tú directamente en el servidor):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://localtur.com
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
MARCA=localtur
MARCA_INDEXAR=false
```

## Importar la semilla

Sube los dos CSV por el Administrador de archivos a `public_html/storage/app/private/` y luego:

```bash
php artisan localtur:importar-semilla storage/app/private/semilla_avisos_publico.csv storage/app/private/semilla_avisos_privado.csv --comuna="Puerto Varas" --simular
php artisan localtur:importar-semilla storage/app/private/semilla_avisos_publico.csv storage/app/private/semilla_avisos_privado.csv --comuna="Puerto Varas"
```

Sin `--comuna` importa todo. Se puede repetir: no duplica y nunca pisa un aviso que su dueño ya activó.

## Después de cada despliegue

```bash
php artisan migrate --force && php artisan optimize
```
