# zeyvro_turnstile — Cloudflare Turnstile Anti-Spam

## Qué hace

Protege el formulario de contacto nativo de PrestaShop 8 (`/contact-us`, módulo `contactform`) contra spam con [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/).

- Inyecta el widget Turnstile antes del botón "Enviar" del formulario de contacto.
- Valida el token en el servidor vía la API de Cloudflare antes de procesar el mensaje.
- Registra todos los intentos (éxito/fallo, IP, user-agent, error codes) en una tabla de log.
- No carga cookies de tracking — compatible con `lgcookieslaw` sin declaración adicional.

## Configuración

### 1. Crear el sitio en Cloudflare Turnstile

1. Accede a [dash.cloudflare.com](https://dash.cloudflare.com).
2. Ve a **Turnstile** → **Add site**.
3. Pon el dominio de la tienda (ej. `zeyvro.com`) y elige el modo:
   - **Managed** (recomendado): Turnstile decide si mostrar un desafío o pasar silenciosamente.
   - **Non-interactive**: Sin interacción del usuario, siempre pasa si el navegador es legítimo.
   - **Invisible**: Sin widget visible, solo verificación en segundo plano.
4. Copia la **Site Key** (pública) y la **Secret Key** (privada).

### 2. Configurar el módulo en PrestaShop

1. Ve al backoffice → **Módulos** → busca "Turnstile" → **Configurar**.
2. Pega la Site Key y la Secret Key.
3. Elige el modo y la acción si falla (`Bloquear` o `Solo registrar`).
4. **Activa el módulo** con el interruptor `Activar Turnstile`.
5. Guarda.

## Cómo verificar que funciona

1. Abre `/contact-us` en el front.
2. Deberías ver el widget Turnstile antes del botón "Enviar" (en modo managed aparece un logo/check de Cloudflare).
3. Abre DevTools → Network.
4. Envía el formulario con el widget completado.
5. Busca la llamada POST al controller. En el payload debe aparecer el campo `cf-turnstile-response` con un token JWT.
6. El mensaje debe enviarse correctamente.
7. Si completas el formulario **sin** el widget (p.ej. borrando el campo `cf-turnstile-response` con DevTools), deberías ver el error: *"La verificación de seguridad no se completó. Por favor, inténtalo de nuevo."*

## Cómo desactivar temporal sin desinstalar

Desde el BO → Configurar módulo → interruptor **Activar Turnstile** → **No** → Guardar.

El módulo queda instalado y con las keys guardadas. Solo se salta la verificación y no se muestra el widget.

## Estructura de la tabla de log

Tabla: `{prefijo}zeyvro_turnstile_log`

| Columna | Tipo | Descripción |
|---|---|---|
| `id_log` | INT UNSIGNED | PK autoincrement |
| `ip` | VARCHAR(45) | IP del visitante (soporte IPv6) |
| `user_agent` | VARCHAR(255) | User-Agent truncado a 255 chars |
| `date_add` | DATETIME | Timestamp del intento |
| `success` | TINYINT(1) | 1 = verificación OK, 0 = fallo |
| `score` | DECIMAL(4,2) | Score de Turnstile (si disponible) |
| `error_codes` | TEXT | Códigos de error separados por coma |

Limpiar logs de más de 30 días: botón en el panel de admin del módulo.

## Flujo técnico

```
Usuario envía formulario de contacto (POST + submitMessage)
  ↓
hookActionFrontControllerSetMedia (nuestro módulo)
  ↓ lee cf-turnstile-response del POST
  ↓ llama a https://challenges.cloudflare.com/turnstile/v0/siteverify
  ↓ si falla + action_on_fail=block → añade error al controller
  ↓ registra en tabla de log
  ↓
contactform::sendMessage() (módulo nativo PS)
  ↓ ve errores existentes → retorna sin enviar email
  ↓
Página re-renderiza con mensaje de error al usuario
```

## Compatibilidad

- PrestaShop 8.x
- Módulo `contactform` nativo (cualquier versión)
- No requiere override de templates
- No interfiere con otros módulos de formulario
