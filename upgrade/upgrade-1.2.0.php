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
 * v1.2.0 — Turnstile también en registro, login, recuperar contraseña, comentarios de prestablog y avisos de
 * reposición de ps_emailalerts, cada uno con su interruptor.
 *
 * Los interruptores se crean APAGADOS: quien sube desde la 1.1.x sigue igual que antes hasta que los encienda
 * en el panel. Solo se crean si no existen, para no apagar los que el cliente ya haya encendido.
 * Registra los hooks nuevos (actionDispatcher y actionSubmitAccountBefore).
 *
 * Como la 1.1.10: invalida en OPcache solo los .php del propio módulo. NUNCA opcache_reset().
 *
 * Idempotente: re-ejecutarlo es inocuo.
 */
function upgrade_module_1_2_0($module)
{
    $keys = [
        'ZEYVRO_TURNSTILE_FORM_REGISTER',
        'ZEYVRO_TURNSTILE_FORM_LOGIN',
        'ZEYVRO_TURNSTILE_FORM_PASSWORD',
        'ZEYVRO_TURNSTILE_FORM_PRESTABLOG',
        'ZEYVRO_TURNSTILE_FORM_EMAILALERTS',
    ];
    foreach ($keys as $key) {
        if (!Configuration::hasKey($key)) {
            Configuration::updateValue($key, 0);
        }
    }
    if (!$module->registerHook('actionDispatcher') || !$module->registerHook('actionSubmitAccountBefore')) {
        return false;
    }

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
                'zeyvro_turnstile upgrade 1.2.0: OPcache not invalidated, the server may still serve the old code',
                2, null, 'zeyvroturnstile', 0, true
            );
        }
    }
    $module->clearAllCaches();

    return true;
}
