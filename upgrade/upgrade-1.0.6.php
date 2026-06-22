<?php
/**
 * @author    Zeyvro
 * @copyright 2026 Zeyvro
 * @license   MIT
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_1_0_6($module)
{
    $module->clearAllCaches();
    return true;
}
