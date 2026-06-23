# Changelog — zeyvro_turnstile

All notable changes are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) · [Semantic Versioning](https://semver.org/)

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
