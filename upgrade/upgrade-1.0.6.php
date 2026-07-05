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
function upgrade_module_1_0_6(Module $module): bool
{
    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }
    @Tools::clearSmartyCache();
    @Media::clearCache();

    return true;
}
