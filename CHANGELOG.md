# Changelog — zeyvro_turnstile

All notable changes are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) · [Semantic Versioning](https://semver.org/)

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
