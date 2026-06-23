<?php

/**
 * Zeyvro - Cloudflare Turnstile for PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    Zeyvro <admin@zeyvro.com>
 * @copyright 2026 Zeyvro
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
/*
 * Upgrade 1.0.8 — Adopta ZeyvroModuleTrait como base común.
 *
 * Cambios: elimina lógica de tab/caché ad-hoc; pasa a usar ensureTabs()
 * idempotente del trait. Registra hook actionAdminControllerSetMedia para
 * auto-reparación en futuros upgrades sin desinstalar.
 * Uninstall pasa a ser preservativo (conserva tabla de logs y configuración).
 *
 * Idempotente: correr 2× no duplica ni rompe nada.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_8(Module $module): bool
{
    try {
        // 1. Registrar hook de auto-reparación si aún no está registrado
        $module->registerHook('actionAdminControllerSetMedia');

        // 2. Marcar schema de tabs como actual para que el hook no reejecuté innecesariamente
        // (ensureTabs() se ejecuta en el siguiente hook BO si el schema no coincide)
        Configuration::updateValue('ZEYVROTURNSTILE_TABV', 'A');

        // 3. Limpiar cachés
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        @Tools::clearSmartyCache();
        @Media::clearCache();

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.0.8 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );

        return true; // nunca WSOD — el botón "Actualizar" nativo queda como fallback
    }
}
