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
 * v1.2.1 — aviso en el panel cuando falta la clave del sitio o la secreta, y «Activado» no se guarda sin las 2.
 *
 * No toca ZEYVRO_TURNSTILE_ENABLED: una tienda ya activada sin clave secreta sigue igual y solo ve el aviso.
 * Como la 1.2.0: invalida en OPcache solo los .php del propio módulo (el controlador cambia). NUNCA opcache_reset().
 *
 * Idempotente: re-ejecutarlo es inocuo.
 */
function upgrade_module_1_2_1($module)
{
    if (filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
        $ok = function_exists('opcache_invalidate');
        if ($ok) {
            try {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(_PS_MODULE_DIR_ . 'zeyvro_turnstile/', FilesystemIterator::SKIP_DOTS)
                );
                foreach ($files as $file) {
                    if ($file->isFile() && strtolower($file->getExtension()) === 'php'
                        && !@opcache_invalidate($file->getPathname(), true)
                    ) {
                        $ok = false;
                    }
                }
            } catch (Throwable $e) {
                $ok = false;
            }
        }
        if (!$ok) {
            PrestaShopLogger::addLog(
                'zeyvro_turnstile upgrade 1.2.1: OPcache not invalidated, the server may still serve the old code',
                2, null, 'zeyvroturnstile', 0, true
            );
        }
    }
    $module->clearAllCaches();

    return true;
}
