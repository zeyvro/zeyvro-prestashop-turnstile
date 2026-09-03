# zeyvro_turnstile — Cloudflare Turnstile Anti-Spam

![Version](https://img.shields.io/badge/version-1.1.6-6C63FF)
![PrestaShop](https://img.shields.io/badge/PrestaShop-8.0%2B-00D9A3)
![PHP](https://img.shields.io/badge/PHP-8.0--8.3-blue)
![License](https://img.shields.io/badge/license-MIT-green)

---

## EN — What it does

Protects the native PrestaShop 8 contact form (`/contact-us`) against spam using [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/).

- Injects the Turnstile widget before the "Send" button.
- Validates the token server-side via the Cloudflare API before processing.
- Logs every attempt (success/failure, IP, user-agent, error codes) in a local table.
- Cookie-free — compatible with `lgcookieslaw` without additional declaration.
- Back-office menu: **CUSTOMIZE → Zeyvro → Anti SPAM**.

### Requirements
- PrestaShop 8.0+ (tested on 8.2.x)
- PHP 8.0–8.3
- Native `contactform` module (any version)
- Active [Cloudflare Turnstile](https://dash.cloudflare.com) site (free)

### Installation
1. Upload the ZIP via **Modules → Upload a module**.
2. The module installs and upgrades automatically — no manual steps.
3. Go to **CUSTOMIZE → Zeyvro → Anti SPAM** and paste your Site Key and Secret Key.
4. Enable the module and save.

### Setup: get Cloudflare keys
1. Log in to [dash.cloudflare.com](https://dash.cloudflare.com).
2. Go to **Turnstile → Add site**.
3. Enter your store domain and choose a mode (Managed recommended).
4. Copy the **Site Key** (public) and **Secret Key** (private).

### Disable without uninstalling
BO → **CUSTOMIZE → Zeyvro → Anti SPAM** → set **Activate Turnstile** to **No** → Save.

### Full cleanup (manual, after uninstall)
The uninstall is **non-destructive**: tables and configuration are preserved so a reinstall restores everything.  
To delete all data permanently, run `sql/uninstall.sql` manually in your DB client after uninstalling.

---

## ES — Qué hace

Protege el formulario de contacto nativo de PrestaShop 8 (`/contact-us`) contra spam con [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/).

- Inyecta el widget Turnstile antes del botón "Enviar".
- Valida el token en el servidor vía la API de Cloudflare antes de procesar el mensaje.
- Registra cada intento (éxito/fallo, IP, user-agent, códigos de error) en una tabla local.
- Sin cookies — compatible con `lgcookieslaw` sin declaración adicional.
- Menú backoffice: **PERSONALIZAR → Zeyvro → Anti SPAM**.

### Requisitos
- PrestaShop 8.0+ (probado en 8.2.x)
- PHP 8.0–8.3
- Módulo `contactform` nativo (cualquier versión)
- Sitio activo en [Cloudflare Turnstile](https://dash.cloudflare.com) (gratuito)

### Instalación
1. Sube el ZIP desde **Módulos → Subir un módulo**.
2. El módulo se instala y actualiza solo — sin pasos manuales.
3. Ve a **PERSONALIZAR → Zeyvro → Anti SPAM** y pega tu Site Key y Secret Key.
4. Activa el módulo y guarda.

### Configurar las claves de Cloudflare
1. Accede a [dash.cloudflare.com](https://dash.cloudflare.com).
2. Ve a **Turnstile → Añadir sitio**.
3. Introduce el dominio de la tienda y elige el modo (Managed recomendado).
4. Copia la **Site Key** (pública) y la **Secret Key** (privada).

### Desactivar sin desinstalar
BO → **PERSONALIZAR → Zeyvro → Anti SPAM** → pon **Activar Turnstile** en **No** → Guardar.

### Limpieza completa (manual, tras desinstalar)
La desinstalación es **preservativa**: tablas y configuración se conservan para que una reinstalación restaure todo.  
Para borrar todos los datos de forma definitiva, ejecuta `sql/uninstall.sql` manualmente en tu cliente de BD tras desinstalar.

---

## Technical reference

### Log table: `{prefix}zeyvro_turnstile_log`

| Column | Type | Description |
|---|---|---|
| `id_log` | INT UNSIGNED | PK autoincrement |
| `ip` | VARCHAR(45) | Visitor IP (IPv6 support) |
| `user_agent` | VARCHAR(255) | User-Agent truncated to 255 chars |
| `date_add` | DATETIME | Attempt timestamp |
| `success` | TINYINT(1) | 1 = OK, 0 = failed |
| `score` | DECIMAL(4,2) | Turnstile score (if available) |
| `error_codes` | TEXT | Error codes comma-separated |

### Verification flow

```
User submits contact form (POST + submitMessage)
  ↓
hookActionFrontControllerSetMedia (this module)
  ↓ reads cf-turnstile-response from POST
  ↓ calls https://challenges.cloudflare.com/turnstile/v0/siteverify
  ↓ if failure + action_on_fail=block → adds error to controller
  ↓ logs to table
  ↓
contactform::sendMessage() (native PS module)
  ↓ sees existing errors → returns without sending email
  ↓
Page re-renders with error message to user
```

### Build ZIP
```
python C:\Dev\_ecosystem\scripts\build-module-zip.py zeyvro_turnstile --base "C:\Dev\zeyvro\modulos-prestashop"
```

---

<!-- ZV-FICHA-MEDIDA:INI -->
## Ficha técnica — ✅ MEDIDA DEL CÓDIGO 2026-09-03

> Todo lo de esta sección sale de leer el código de la versión **1.1.6** en disco.
> Fichero principal: `zeyvro_turnstile.php`.

### Qué hace (respaldado por el código)

- Inserta el widget de Cloudflare Turnstile en el formulario de contacto nativo, cargando `https://challenges.cloudflare.com/turnstile/v0/api.js` (linea 155).
- Valida el token contra `https://challenges.cloudflare.com/turnstile/v0/siteverify` por cURL (lineas 232-252).
- **Registra cada verificacion en su tabla propia**: `Db::getInstance()->insert('zeyvro_turnstile_log', [...])` en `zeyvro_turnstile.php:280`, guardando ip, user agent, exito/fallo, score y codigos de error.
- **Cuenta los registros en el backoffice**: `AdminZeyvroTurnstileController::getLogCount()` ejecuta `SELECT COUNT(*) FROM \`_DB_PREFIX_zeyvro_turnstile_log\`` (linea 271), y el controlador lista las filas con `SELECT *` (linea 262) sobre un helper de lista (`$this->table = 'zeyvro_turnstile_log'`, lineas 18 y 242).
- Si la llamada cURL falla, escribe ademas en `PrestaShopLogger` con severidad 3 (linea 255).
- Si la validacion no pasa y `ZEYVRO_TURNSTILE_ACTION_ON_FAIL` no es `log_only`, bloquea el envio (linea 218).

### Hooks

| Hook registrado | Método que lo implementa |
|---|---|
| `displayHeader` | `hookDisplayHeader()` |
| `actionFrontControllerSetMedia` | `hookActionFrontControllerSetMedia()` |
| `displayBeforeBodyClosingTag` | `hookDisplayBeforeBodyClosingTag()` |

### Ajustes de configuración (nombre exacto de la clave)

| Clave `Configuration::` | Para qué |
|---|---|
| `ZEYVRO_TURNSTILE_ENABLED` | activa o desactiva la proteccion |
| `ZEYVRO_TURNSTILE_SITE_KEY` | site key publica de Cloudflare Turnstile |
| `ZEYVRO_TURNSTILE_SECRET_KEY` | secret key de Cloudflare (server-side) |
| `ZEYVRO_TURNSTILE_MODE` | modo del widget; **por defecto `managed`** (linea 112) |
| `ZEYVRO_TURNSTILE_ACTION_ON_FAIL` | que hacer si falla; **por defecto `block`**, alternativa medida `log_only` (lineas 113 y 218) |
| `ZEYVRO_TURNSTILE_API_TIMEOUT` | timeout de la llamada a Cloudflare |
| `ZEYVRO_TURNSTILE_LOG_ENABLED` | registrar intentos en el log de PrestaShop |
| `ZEYVROTURNSTILE_VERSION` | version instalada |
| `ZEYVROTURNSTILE_TABV` | version de schema de tabs, para la auto-reparacion |
| `ZEYVRO_PROMO_FEED_CACHE` | cache del feed de cards promocionales Zeyvro (la pone el trait compartido) |
| `ZEYVRO_PROMO_FEED_TS` | timestamp de ese cache (trait compartido) |

### Tablas de base de datos

- `**`zeyvro_turnstile_log`** - tabla PROPIA del modulo. Columnas medidas en `sql/install.sql`: `id_log` (PK autoinc), `ip` VARCHAR(45), `user_agent` VARCHAR(255), `date_add` DATETIME, `success` TINYINT(1), `score` DECIMAL(4,2), `error_codes` TEXT, con indice `idx_date` sobre `date_add`.`
- `Se crea en DOS sitios: `sql/install.sql` y el propio `zeyvro_turnstile.php:92`. Se borra en `sql/uninstall.sql` (`DROP TABLE IF EXISTS`).`

### Compatibilidad, licencia y motor de licencia

| Dato | Valor medido |
|---|---|
| `ps_versions_compliancy` | `'min' => '8.0.0', 'max' => '9.99.99'` |
| Licencia | MIT, fichero `LICENSE` presente |
| `ZV_LICENSE_TYPE` | `'free'` |
| `LICENSE_ENABLED` | no declarada |
| Motor de licencia LemonSqueezy | **No** |
| `ZeyvroModuleTrait` | `use ZeyvroModuleTrait` - aporta menu padre Zeyvro, tab hijo, auto-reparacion de tabs y cards promocionales |
| Tab en el menú Zeyvro | `AdminZeyvroTurnstile` - tab 'Anti SPAM' en el menu Zeyvro |
| `ZV_ADS_VARIANT` | `free` |

### Estructura relevante

- `sql/install.sql` + `sql/uninstall.sql` - creacion y borrado de la tabla de log
- `controllers/admin/AdminZeyvroTurnstileController.php` - ajustes **y listado del log**
- `views/templates/front/turnstile_widget.tpl` - widget en el formulario
- `vendor/` - dependencias composer embarcadas
- `upgrade/` - 14 scripts, de 1.0.1 a 1.1.4

### Afirmaciones que el código SÍ respalda

> Lista de contraste para auditar contenido de marketing. Si una afirmación no está aquí, el código no la respalda.

- Protege el formulario de contacto de PrestaShop con Cloudflare Turnstile.
- Verificacion server-side real contra el endpoint `siteverify` de Cloudflare.
- **Registra los bloqueos en su propia tabla `zeyvro_turnstile_log` y los cuenta en el backoffice** (`getLogCount()`).
- Guarda por intento: ip, user agent, fecha, exito/fallo, score y codigos de error de Cloudflare.
- Modo permisivo disponible: `ACTION_ON_FAIL='log_only'` registra en vez de bloquear.
- Crea un tab propio 'Anti SPAM' en el menu Zeyvro (`AdminZeyvroTurnstile`).
- Sin motor de licencia (`ZV_LICENSE_TYPE='free'`).

### No deducible del código

- **Una cifra concreta de eficacia (por ejemplo '850 -> 70' o '-92%') requiere aportar la consulta sobre una instalacion real.** El codigo da el mecanismo -- tabla de log con `success` y contador en el BO -- pero no una cifra: no esta respaldada por el codigo en abstracto, y tampoco es imposible de respaldar. Para sostenerla hace falta el `SELECT` sobre la tienda concreta y la fecha del corte.
- Proteccion de otros formularios (registro, resenas): solo se engancha al de contacto.
<!-- ZV-FICHA-MEDIDA:FIN -->
