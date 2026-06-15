# zeyvro_turnstile — Cloudflare Turnstile Anti-Spam

![Version](https://img.shields.io/badge/version-1.0.8-6C63FF)
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
