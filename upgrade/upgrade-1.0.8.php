<?php
/**
 * Upgrade 1.0.8 — Adopta ZeyvroModuleTrait como base común.
 *
 * Cambios: elimina lógica de tab/caché ad-hoc; pasa a usar ensureTabs()
 * idempotente del trait. Registra hook actionAdminControllerSetMedia para
 * auto-reparación en futuros upgrades sin desinstalar.
 * Uninstall pasa a ser preservativo (conserva tabla de logs y configuración).
 *
 * Idempotente: correr 2× no duplica ni rompe nada.
 * @license   MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_8($module)
{
    try {
        // 1. Normalizar tabs con el nuevo ensureTabs() idempotente del trait
        $module->ensureTabs();

        // 2. Registrar hook de auto-reparación si aún no está registrado
        $module->registerHook('actionAdminControllerSetMedia');

        // 3. Marcar schema de tabs como actual para que el hook no reejecuté innecesariamente
        Configuration::updateValue('ZEYVROTURNSTILE_TABV', 'A');

        // 4. Limpiar cachés
        $module->clearAllCaches();

        return true;
    } catch (\Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.0.8 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );
        return true; // nunca WSOD — el botón "Actualizar" nativo queda como fallback
    }
}
