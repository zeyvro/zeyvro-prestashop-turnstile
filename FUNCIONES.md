# FUNCIONES — zeyvro_turnstile 1.2.0

- Fecha: 2026-10-09 (Code, sesión de la 1.2.0)
- Medido en: el código de la rama `feature/v1.2.0-mas-formularios` de este repo, el mismo que empaqueta `build-module-zip.py` en `C:\Dev\releases\zeyvro_turnstile\1.2.0\`.
- Líneas: `fichero:inicio-fin` (inicio = firma de la función; fin = su llave de cierre). Sin fichero = `zeyvro_turnstile.php`. Plantilla = `views/templates/front/turnstile_widget.tpl`.
- Índice: paso 4 de la vuelta funcional sobre la 1.1.10 (F1-F13). La 1.2.0 añade F14-F22.

| ID | Función | Dónde | Qué hace |
|---|---|---|---|
| F1 | Constructor | `__construct` :57-78 | Versión 1.2.0, compatibilidad 8.0.0-9.1.3 y, en el BO, auto-upgrade (F12). |
| F2 | Instalación | `install` :84-95 | Base común del trait, tabla, configuración y 5 hooks: `displayHeader`, `displayBeforeBodyClosingTag`, `actionFrontControllerSetMedia`, `actionDispatcher` y `actionSubmitAccountBefore` (los dos últimos desde la 1.2.0). |
| F3 | Desinstalación conservadora | `uninstall` :97-109 | Quita tab y hooks; la tabla de log y la configuración se conservan. |
| F4 | Tabla de log | `installSql` :111-126 | Crea `zeyvro_turnstile_log` si no existe. |
| F5 | Configuración por defecto | `installConfig` :128-150 | 7 claves de la 1.1.x y, desde la 1.2.0, los 5 interruptores `ZEYVRO_TURNSTILE_FORM_*` a 1. |
| F6 | Panel | `getContent` :152-157 | Redirige al controlador `AdminZeyvroTurnstile`. |
| F7 | api.js en el contacto | `hookDisplayHeader` :166-188 | Como en la 1.1.10: solo en `contact`, con la clave de sitio puesta. |
| F8 | Pintado del widget | `hookDisplayBeforeBodyClosingTag` :193-231 · plantilla `sbtInit` :221-237 | Mapa `$forms` (:201-209): contacto en `contact`; registro en `registration`; login en todas las páginas si el visitante no ha iniciado sesión; contraseña en `password`; prestablog en sus controladores; ps_emailalerts en `product`. Pasa a la plantilla los formularios activos, la URL de login, los formularios que han fallado (menos prestablog) y el mensaje de error. |
| F9 | Validación del contacto + mensajes diferidos | `hookActionFrontControllerSetMedia` :236-282 | Contacto igual que en la 1.1.10 (:250-281). Desde la 1.2.0, al principio (:238-248): si falló prestablog, mete el error en `controller->errors`; si falló ps_emailalerts, responde `{"error":true,"message":…}` y termina. |
| F10 | Llamada a Cloudflare | `verifyCloudflareTurnstile` :408-449 | POST a `siteverify` por cURL con timeout. |
| F11 | Log de intentos | `logVerification` :451-467 | Una fila por verificación si el log está activo. |
| F12 | Auto-upgrade | `runAutoUpgrade` :473-547 · `isModuleManagerRequest` :554-559 | En el BO ejecuta los `upgrade/*.php` pendientes, salvo en `/improve/modules/`. |
| F13 | Upgrades 1.0.1-1.1.10 | `upgrade/upgrade-*.php` | Sin cambios en la 1.2.0. |
| F14 | Interruptor por formulario | `isFormOn` :292-305 · `FORM_KEYS` :46-52 | Interruptor general + el del formulario; para prestablog y ps_emailalerts, además el módulo instalado y activo. |
| F15 | Módulo del controlador actual | `currentModuleName` :310-319 | Nombre del módulo de un `ModuleFrontController`, o `''`. |
| F16 | Verificación común | `verifyFormToken` :325-343 · `$sbtFailed` :55 | Sin clave secreta deja pasar; verifica, registra y, si falla y no es `log_only`, marca el formulario como fallido y devuelve false. |
| F17 | Registro | `hookActionSubmitAccountBefore` :351-360 | Solo con `php_self` = `registration` (el checkout de 8.2+ también dispara el hook y ahí devuelve true); devuelve el resultado de F16 y el núcleo no crea la cuenta si es false. |
| F18 | Login, contraseña, prestablog y ps_emailalerts en el servidor | `hookActionDispatcher` :370-402 | Antes de `Controller::run()` (también con `ajax=1`). Login: `authentication` + `submitLogin`; contraseña: `password` + `email`; prestablog: `submitComment`; ps_emailalerts: `process=add`. Si F16 falla, quita esa clave de `$_POST`/`$_GET`. |
| F19 | Panel: interruptores | `controllers/admin/AdminZeyvroTurnstileController.php` `saveSettings` :58-109 (claves :68-72) · `formSwitch` :154-171 · `renderSettingsForm` :173-306 (:187-211) | 5 interruptores Sí/No guardados como enteros. |
| F20 | Widget de los formularios nuevos | plantilla `renderPending` :79-96 · `loadApi` :98-115 · `addWidget` :117-152 · `submitButton` :154-156 · `postsTo` :159-168 · `sbtInjectForms` :191-219 | Render explícito; carga `api.js?render=explicit` si la página no lo trae; error en el propio formulario si falló; login solo en formularios que envían a la URL de login, y fuera de la página de login api.js al primer foco. |
| F21 | Token de ps_emailalerts | plantilla `sbtMailalertsToken` :171-189 | `jQuery.ajaxPrefilter` sobre su POST `process=add`: añade `cf-turnstile-response` y reinicia el widget tras la respuesta. |
| F22 | Upgrade 1.2.0 | `upgrade/upgrade-1.2.0.php:25-71` | Crea los 5 interruptores a 0 solo si no existen, registra `actionDispatcher` y `actionSubmitAccountBefore`, invalida en OPcache solo los `.php` del módulo y limpia su caché. |
