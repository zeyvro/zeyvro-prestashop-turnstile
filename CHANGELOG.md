# Changelog

## 1.0.6 — 2026-06-13

### Añadido
- §7.1 Auto-upgrade al subir ZIP por BO: `runAutoUpgrade()` en `__construct()` — detecta versión instalada vía `ZEYVROTURNSTILE_VERSION` / tabla `ps_module`, lee destino de `config.xml`, ejecuta upgrade scripts intermedios en orden semver, actualiza BD automáticamente.
- §2.1 `clearAllCaches()`: OPcache + Smarty + CCC + autoload. `try/catch \Throwable` (PHP 8.0 safe).
- `upgrade/upgrade-1.0.6.php`: idempotente.

All notable changes to zeyvro-prestashop-turnstile are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) — [Semantic Versioning](https://semver.org/).

## [1.0.5] - 2026-06-12
### Fixed
- fix: class naming PS8 coreLoadModule — el módulo no instalaba en PS8 (exit 255, `get_parent_class(): bool` en `ModuleRepository.php:252`). Clase principal renombrada `ZeyvroTurnstile` → `Zeyvro_Turnstile`: PS8 hace `class_exists()` con el nombre literal del módulo, y al llevar el name underscore la clase debe llevarlo también (patrón oficial `Ps_Emailsubscription`). **v1.0.4 y anteriores no instalan en PrestaShop 8 — actualizar.**

## [1.0.4] - 2026-06-04
### Fixed
- `AdminZeyvroTurnstileController`: referencia a clase `Zeyvroturnstile::CONFIG_KEYS` corregida a `ZeyvroTurnstile::CONFIG_KEYS` (PHPStan — clase inexistente).
- `postProcess()` e `initContent()` en `AdminZeyvroTurnstileController`: añadido return type `: void` para coincidir con la firma del padre.
- `install()` y `uninstall()` en `ZeyvroTurnstile`: añadido return type `: bool` para coincidir con `Module`.

## [1.0.3] - 2026-05-19
### Changed
- Rebranded from SensaBien internal module to Zeyvro public release
- Module ID, class names and constants migrated to `zeyvro_turnstile` / `ZEYVRO_TURNSTILE_*`
- English as primary display language

## [1.0.2] - 2026-04-30
### Fixed
- Token verification timeout handling — configurable via `ZEYVRO_TURNSTILE_API_TIMEOUT`
- Log table cleanup on uninstall now conditional on config flag

## [1.0.1] - 2026-04-28
### Added
- `log_only` mode: log failed verifications without blocking the form submission
- Admin log viewer with IP, user-agent, timestamp and error codes

## [1.0.0] - 2026-04-26
### Added
- Initial release
- Cloudflare Turnstile widget injected on PrestaShop 8 native contact form
- Server-side token verification via Cloudflare siteverify API
- Three widget modes: managed, non-interactive, invisible
- Compatible with lgcookieslaw (widget deferred until consent)
- Settings page: BO → Modules → Zeyvro Turnstile
