<?php
/**
 * Upgrade 1.0.10 — Compatibilidad PS9.
 *
 * Cambios: ps_versions_compliancy max fijado a '9.99.99'.
 * Wrapper l() PS9-safe añadido al AdminController.
 * Idempotente: correr 2× no duplica ni rompe nada.
 *
 * @author  Zeyvro <hola@zeyvro.com>
 * @license MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_10($module)
{
    try {
        $module->clearAllCaches();

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.0.10 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );

        return true;
    }
}
