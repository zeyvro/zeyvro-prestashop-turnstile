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
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_10(Module $module): bool
{
    try {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        @Tools::clearSmartyCache();
        @Media::clearCache();

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.0.10 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );

        return true;
    }
}
