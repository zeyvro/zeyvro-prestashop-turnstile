# Changelog — zeyvro_turnstile

All notable changes are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) · [Semantic Versioning](https://semver.org/)

---

## 1.1.8 — 2026-09-23

### Corregido (ES)
- **PrestaShop 8.0 y 8.1: un mensaje rechazado por Turnstile ya no se guarda en Servicio al cliente.** Con la acción «Bloquear», el cliente veía el aviso de error y el correo no salía, pero el mensaje quedaba guardado igualmente en **Clientes → Servicio al cliente**. Pasaba con el módulo de contacto de PrestaShop anterior a la 4.4.3, el que traen PS 8.0 y 8.1. Ahora un envío rechazado no llega a procesarse: el cliente ve el aviso y conserva en el formulario su email y el texto que había escrito, para volver a intentarlo.
- En PrestaShop 8.2 y 9 no cambia nada para la tienda: ahí el mensaje rechazado ya no se guardaba.
- `upgrade/upgrade-1.1.8.php`: sin cambios en la base de datos; limpia la caché del módulo. Idempotente.
- **Aviso conocido — si actualizas desde la 1.1.6 en PrestaShop 9.1:** en algunos alojamientos puede aparecer un aviso de error al subir el ZIP. Recarga la página: la actualización ya está hecha.
- Desde la 1.1.7, la actualización a la 1.1.8 en PrestaShop 9.1 ya no muestra ese aviso.

### Fixed (EN)
- **PrestaShop 8.0 and 8.1: a message rejected by Turnstile is no longer saved in Customer Service.** With the "Block" action, the customer saw the error and no email was sent, but the message was still saved in **Customers → Customer Service**. It happened with the PrestaShop contact module older than 4.4.3, the one shipped with PS 8.0 and 8.1. A rejected submission is now never processed: the customer sees the error and keeps their email and the text they wrote in the form, so they can try again.
- Nothing changes for the shop on PrestaShop 8.2 and 9: there the rejected message was already not saved.
- `upgrade/upgrade-1.1.8.php`: no database change; clears the module cache. Idempotent.
- **Known notice — if you upgrade from 1.1.6 on PrestaShop 9.1:** on some hosting setups an error notice may appear when you upload the ZIP. Reload the page: the upgrade is already done.
- From 1.1.7 on, upgrading to 1.1.8 on PrestaShop 9.1 no longer shows that notice.

## 1.1.7 — 2026-09-12

### Corregido (ES)
- **Aviso falso al actualizar desde el BO en PrestaShop 9.1.** Al subir el ZIP de una versión nueva, PS 9.1 puede responder «No se han hecho actualizaciones… El módulo zeyvro_turnstile no se puede actualizar ahora mismo» aunque la actualización sí se haya hecho (base de datos y código en la versión nueva). Causa: el auto-upgrade del módulo (`runAutoUpgrade()`, en el constructor) ejecutaba los `upgrade/*.php` dentro de la misma petición en la que PS 9.1 los vuelve a ejecutar; el core los ve ya cargados, los marca como duplicados (`classes/module/Module.php`, `runUpgradeModule()`) y devuelve error.
- `runAutoUpgrade()` ya no hace nada en las peticiones de gestión de módulos del BO (rutas `/improve/modules/…`: subir el ZIP, «Actualizar»), donde PrestaShop ejecuta él mismo los scripts. En el resto de páginas del BO sigue funcionando igual: PS 9.0.0 no aplica los scripts al subir el ZIP y es el auto-upgrade el que lo cubre.
- `upgrade/upgrade-1.1.7.php`: sin cambios de esquema. Invalida en OPcache solo los ficheros `.php` del módulo (nunca la caché de toda la tienda) y limpia la caché del módulo. Idempotente.
- **Al actualizar a esta versión desde una anterior**, PrestaShop 9.1 todavía puede mostrar ese aviso: en esa subida el servidor ejecuta aún el código de la versión que tenías instalada. **La actualización se ha realizado.** Desde la 1.1.7, las siguientes actualizaciones ya no lo muestran.

### Fixed (EN)
- **False warning when upgrading from the Back Office on PrestaShop 9.1.** When uploading the ZIP of a new version, PS 9.1 may answer "No update has been applied… Module zeyvro_turnstile cannot be updated this time" even though the upgrade has been applied (database and code on the new version). Cause: the module's auto-upgrade (`runAutoUpgrade()`, in the constructor) ran the `upgrade/*.php` scripts inside the same request in which PS 9.1 runs them again; the core finds them already loaded, flags them as duplicates (`classes/module/Module.php`, `runUpgradeModule()`) and returns an error.
- `runAutoUpgrade()` now does nothing on Back Office module-management requests (`/improve/modules/…` routes: ZIP upload, "Upgrade"), where PrestaShop runs the scripts itself. It keeps working on every other Back Office page: PS 9.0.0 does not apply the scripts when the ZIP is uploaded and the auto-upgrade covers that.
- `upgrade/upgrade-1.1.7.php`: no schema change. It invalidates only the module's own `.php` files in OPcache (never the whole shop's cache) and clears the module cache. Idempotent.
- **When upgrading to this version from an older one**, PrestaShop 9.1 may still show that warning: during that upload the server still runs the code of the version you had installed. **The upgrade has been applied.** From 1.1.7 on, later upgrades no longer show it.

## 1.1.6 — 2026-07-06

### Changed
- `classes/ZeyvroModuleTrait.php`: canon reformateado al estándar oficial PS (llaves, `zvTabIdFromClassName`), sha256 `f51419f3441b7d17e233ba6d8873bfbb32f3a847909d3f748035586077be7d61` (antes `1c0a27c2...`, formato `if/endif`). Validado 9/9 VERDE en el Validator oficial para el módulo hermano `zeyvro_producttabs` (run 3744711).
- `.php-cs-fixer.dist.php`: config simplificada a la clase oficial `PrestaShop\CodingStandards\CsFixer\Config` con un único override (`blank_line_after_opening_tag => false`); ya no excluye el trait del Finder (el canon nuevo ya está en formato oficial, cs-fixer no lo re-modifica).
- Sin cambios de comportamiento ni de base de datos. Pendiente de que el Validator oficial confirme 9/9 antes de publicar esta versión.

## 1.1.5 — 2026-07-04

### Fixed
- **Trait unificado al canon FULL** (`_shared/ZeyvroModuleTrait.php`): `classes/ZeyvroModuleTrait.php` sustituido por copia verbatim del canon único de la Tanda Trait Fase B — fix del bug opcache en `clearAllCaches()` (`opcache_reset()`/`generateIndex()` síncronos en `install()` podían saturar el pool PHP-FPM). Sin cambio de comportamiento observable: el módulo no invoca `renderZeyvroAds()`.

### Added
- `upgrade/upgrade-1.1.5.php`: script de upgrade idempotente (limpieza de caché, sin opcache_reset).

---

## 1.1.4 — 2026-06-23

### Fixed
- **Security / .htaccess raíz**: el `.htaccess` anterior usaba `Require all denied` (Apache 2.4) y `Deny from all` (Apache 2.2) de forma global, bloqueando todos los ficheros del módulo, incluidos `views/css/*.css` y `views/js/*.js`. Con CCC desactivado Apache servía directamente esos assets → HTTP 403. Se reemplaza por `Options -Indexes` (impide listado de directorio) + `<FilesMatch "\.php$">` (bloquea solo ejecución directa de PHP). Assets estáticos se sirven con 200.

### Added
- `upgrade/upgrade-1.1.4.php`: script de upgrade idempotente (limpieza de caché).

---

## 1.1.3 — 2026-06-23

### Fixed (Validator PS — Licenses)
- **LICENSES**: revertida la regla `blank_line_after_opening_tag` a `false`. Habilitarla en v1.1.2 rompió Licenses 0→23 (módulo 3740522): el Validator PS exige "There must be no blank lines before the file comment" (la cabecera `/** licencia */` debe ir pegada a `<?php`). La blank line introducida por v1.1.2 se elimina de los 23 ficheros PHP del módulo.
- `phpdoc_to_comment => true` se mantiene (no afecta Licenses; solo convierte `/**` secundarios no estructurales a `/*`).
- **Standards residuales por `blank_line_after_opening_tag` son IRREDUCIBLES**: contradicción interna del Validator (exige la blank line en Standards pero la prohíbe en Licenses). Documentado en `.php-cs-fixer.dist.php`.

### Added
- `upgrade/upgrade-1.1.3.php`: script de upgrade idempotente (limpieza de caché).

### Changed
- Gold template (zeyvro_template / feature/ps9-scaffold-fixes): `.php-cs-fixer.dist.php` propagado con `blank_line_after_opening_tag => false` y la misma barrera documentada.

---

## 1.1.2 — 2026-06-23

### Fixed (Validator PS — Standards)
- **STANDARDS**: habilitadas las reglas `blank_line_after_opening_tag` y `phpdoc_to_comment` en `.php-cs-fixer.dist.php`. PHP CS Fixer 3.x preserva el primer `/**` de cada fichero (file-header de licencia) como `/**`; solo convierte `/**` secundarios no estructurales a `/*`. Los `index.php` de seguridad y los headers de licencia quedan intactos (Licenses sigue en 0). Verificado dry-run = 0 pendientes.
- **STANDARDS**: en `upgrade/upgrade-1.0.7.php` y `upgrade/upgrade-1.0.8.php`, el bloque de descripción `/**` convertido a `/*` (era un docblock no estructural).
- Blank line añadida tras `<?php` en los 22 ficheros PHP del módulo (regla `blank_line_after_opening_tag`).
- La barrera irreducible `no_alternative_syntax => false` se mantiene (guard del trait `ZeyvroModuleTrait.php`).

### Added
- `upgrade/upgrade-1.1.2.php`: script de upgrade idempotente (limpieza de caché).

### Changed
- Gold template (zeyvro_template / feature/ps9-scaffold-fixes): `.php-cs-fixer.dist.php` propagado con las mismas 2 reglas habilitadas. Los módulos futuros nacen ya conformes.

---

## 1.1.1 — 2026-06-23

### Fixed (Validator PS — Requirements + Compatibility + Optimizations + Standards)
- **REQUIREMENTS**: `$this->tab` cambiado de `'other'` (valor inválido) a `'front_office_features'`. Mismo valor en `config.xml <tab>`.
- **COMPATIBILITY**: eliminada la funcionalidad "Zeyvro Ads" (`renderZeyvroAds()` del trait y la llamada en `AdminZeyvroTurnstileController`). Motivo: Addons PS no permite promocionar productos/enlaces fuera de Addons.
- **COMPATIBILITY**: reemplazadas todas las llamadas `$module->clearAllCaches()` y `$module->ensureTabs()` en scripts de upgrade (`1.0.6`, `1.0.7`, `1.0.8`, `1.0.10`, `1.1.0`) por llamadas directas a las funciones estándar de PS (`Tools::clearSmartyCache()`, `Media::clearCache()`, `opcache_reset()`). El Validator no resuelve métodos de trait sobre el tipo `Module` en parámetros de funciones.
- **OPTIMIZATIONS**: `hookDisplayHeader` ya no retorna HTML inline con `<script src="...">`. Registra el JS de Turnstile vía `registerJavascript()` (server remote, position head, async defer).
- **STANDARDS**: php-cs-fixer 0 archivos pendientes. Barreras irreducibles documentadas en `.php-cs-fixer.dist.php` (no_alternative_syntax del guard trait, blank_line_after_opening_tag).

### Added
- `upgrade/upgrade-1.1.1.php`: script de upgrade idempotente (limpieza de caché directa).

---

## 1.1.0 — 2026-06-23

### Changed (Validator PS — esqueleto canónico)
- **Licencias (objetivo: 0)**: cabecera de licencia LARGA (NOTICE OF LICENSE + MIT + @copyright) aplicada a TODOS los .php del módulo. Sustituye el bloque de una línea `@license MIT` anterior. Formato del generador oficial PrestaShop.
- **config.xml**: `<displayName>` cambiado de `Turnstile Anti-Spam` a `Zeyvro Turnstile` para coincidir exactamente con `$this->displayName`. `<version>` → 1.1.0.
- **i18n / inglés**: todos los strings fuente en PHP y Smarty traducidos al inglés. Hashes de `en.php` y `es.php` regenerados en consecuencia.
- **Compatibilidad**: `Language::getLanguages(true)` protegido con `?: []` en `ZeyvroModuleTrait.php` y `upgrade-1.0.7.php`.
- **Anuncios**: textos de `renderZeyvroAds()` en inglés.
- **php-cs-fixer**: 0 archivos modificados (código ya conforme).

### Added
- `upgrade/upgrade-1.1.0.php`: script de upgrade idempotente (ensureTabs + clearAllCaches).
- Guard `_PS_VERSION_` en todos los `index.php` de seguridad que no lo tenían.

---

## 1.0.10 — 2026-06-22

### Fixed (Fase 3 — PS9 compat)
- **PS9 BLOCKER**: `ModuleAdminControllerCore::l()` fue eliminado en PS9 — añadido wrapper `l()` PS9-safe en `AdminZeyvroTurnstileController` con la firma compatible con PS8 (`$string, $class, $addslashes, $htmlentities`). Sin este fix el panel lanzaba `UndefinedMethodError` → HTTP 500 en PS9.
- **PS9 BLOCKER**: `ps_versions_compliancy max` era `_PS_VERSION_` → corregido a `'9.99.99'`. Con `_PS_VERSION_` el módulo bloqueaba instalación en PS9 con mensaje "This module is not compatible with your version of PrestaShop".
- `config.xml` `max` actualizado a `9.99.99`.

### Fixed (Fase 4 — Verified+)
- **Seguridad**: eliminados `{chr(36)}form_html nofilter}` y `{chr(36)}zeyvro_ads nofilter}` de `settings.tpl`. El form se renderiza via `$this->content` directamente (patrón gold template). Las ads se concatenan en PHP, sin inyección de HTML raw en Smarty. Validator check 14: 0 nofilter.
- **Compatibilidad**: `->active = 1` → `->active = true` en `ZeyvroModuleTrait.php`, `upgrade-1.0.3.php`, `upgrade-1.0.7.php`.
- **Compatibilidad**: return types añadidos en los 3 métodos hook (`hookDisplayHeader`, `hookDisplayBeforeBodyClosingTag`, `hookActionFrontControllerSetMedia`).
- **Licencias .tpl**: cabecera `@license MIT` (comentario Smarty `{* *}`) añadida a `settings.tpl` y `turnstile_widget.tpl`. EOL LF.

### Added (Fase 4 — Verified+)
- `.php-cs-fixer.dist.php`: ruleset PS oficial con las 3 barreras irreducibles documentadas.
- `phpstan.neon` paths: `classes/` incluida; `clearSf2Cache` añadido a stubs. PHPStan 0 errores.

### Changed (Fase 3)
- `composer.json` `license`: `proprietary` → `MIT` (coherente con módulo free).

### Removed (Fase 3)
- `config_es.xml` eliminado (0 referencias en código; solo causaba warnings en el Validator).

---

## 1.0.8 — 2026-06-15

### Added
- **Base común `ZeyvroModuleTrait`**: tab/caché/auto-reparación/publicidad escritos una sola vez en `classes/ZeyvroModuleTrait.php` (fuente canónica: `_shared/`). PROHIBIDO reimplementar por módulo.
- `ensureTabs()` idempotente sin early-return: siempre normaliza el estado del tab hijo (id_parent, active, name, icon, module) aunque ya exista. Consolida duplicados de `AdminZeyvroParent`.
- Hook `actionAdminControllerSetMedia`: auto-reparación de tabs guarded por flag `ZEYVROTURNSTILE_TABV`. Se cura solo tras subir el ZIP sin desinstalar ni limpiar caché manualmente.
- `renderZeyvroAds()`: bloque de publicidad estático (sin llamadas remotas) con escaparate de módulos Zeyvro de pago + UTM. Reemplaza el banner hardcoded anterior.
- Traducciones ES+EN en `translations/es.php` y `translations/en.php`.
- `upgrade/upgrade-1.0.8.php`: idempotente — ensureTabs + registerHook(auto-repair) + flag + clearAllCaches.

### Changed
- `install()` y `uninstall()` refactorizados para usar `installBase()`/`uninstallBase()` del trait.
- `uninstall()` ahora **preservativo**: conserva tabla de logs y configuración (site key, secret key). Reinstalar restaura todo. Ver `sql/uninstall.sql` para limpieza manual completa.
- README reescrito bilingüe EN+ES con badges y referencia al flujo técnico.
- `config.xml` y `config_es.xml` actualizados a 1.0.8 con `ps_versions_compliancy` explícita.

### Removed
- Métodos `installTab()`, `uninstallTab()`, `createTabRoles()` del módulo principal (ahora en el trait).

---

## 1.0.7 — 2026-06-15

### Fixed
- Menú BO: sustituido patrón incorrecto (AdminParentLocalization / AdminZeyvroGroup) por el canónico `AdminZeyvroParent` bajo IMPROVE — tab aparece ahora en PERSONALIZAR → Zeyvro → Anti SPAM junto a SEO Redirects.
- `installTab()` reescrito: crea/reusa `AdminZeyvroParent` con `module=''` (compartido), hijo `AdminZeyvroTurnstile` con nombre "Anti SPAM" e icono `verified_user`.
- `uninstallTab()` corregido: borra el child y el parent solo si no quedan otros hijos.
- `createTabRoles()` canónico (ROLE_MOD_TAB_*).
- `config_es.xml` sincronizado a 1.0.7.

### Añadido
- `upgrade/upgrade-1.0.7.php`: reparación idempotente del estado en demo.

---

## 1.0.6 — 2026-06-13

### Añadido
- §7.1 Auto-upgrade al subir ZIP por BO: `runAutoUpgrade()` en `__construct()`.
- §2.1 `clearAllCaches()`: OPcache + Smarty + CCC + autoload.
- `upgrade/upgrade-1.0.6.php`: idempotente.

---

## 1.0.5 — 2026-06-12

### Fixed
- Clase principal renombrada `ZeyvroTurnstile` → `Zeyvro_Turnstile`: PS8 require que el nombre de clase coincida literalmente con el `name` del módulo (patrón `Ps_Emailsubscription`). v1.0.4 y anteriores no instalaban.

---

## 1.0.4 — 2026-06-04

### Fixed
- Referencia a clase `ZeyvroTurnstile::CONFIG_KEYS` corregida.
- Return types `: void` en controlador y `: bool` en install/uninstall.

---

## 1.0.3 — 2026-05-19

### Changed
- Rebrand de módulo interno SensaBien a Zeyvro público.

---

## 1.0.2 — 2026-04-30

### Fixed
- Timeout configurable en verificación de token.

---

## 1.0.1 — 2026-04-28

### Added
- Modo `log_only`: registra fallos sin bloquear el formulario.
- Visor de log en el panel de admin.

---

## 1.0.0 — 2026-04-26

### Added
- Versión inicial.
