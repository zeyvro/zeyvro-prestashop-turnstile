<?php
/**
 * Zeyvro PrestaShop Module
 *
 * @author    Zeyvro <admin@zeyvro.com>
 * @copyright 2026 Zeyvro
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * v1.1.6 — canon ZeyvroModuleTrait.php reformateado a estandar oficial PS
 * (llaves, zvTabIdFromClassName) + config cs-fixer a 1 barrera. Sin cambios
 * de BD ni de tabs. Idempotente: re-ejecutar es inocuo.
 */
function upgrade_module_1_1_6(Module $module): bool
{
    try {
        @Tools::clearSmartyCache();
        @Media::clearCache();

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.1.6 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );

        return true;
    }
}
