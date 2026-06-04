<?php
/**
 * Bootstrap minimo para PHPStan con mirror PS.
 * Patron PS: Module.php define ModuleCore; el alias Module se crea en tiempo de ejecucion.
 * Este bootstrap replica ese patron para PHPStan.
 */

// 1. _PS_ROOT_DIR_ desde env
if (!defined('_PS_ROOT_DIR_')) {
    $psRoot = getenv('_PS_ROOT_DIR_') ?: 'C:/Dev/_mirror/sensabien-dev';
    define('_PS_ROOT_DIR_', str_replace('\\', '/', realpath($psRoot)));
}

// 2. Constantes PS (defines.inc.php no tiene require de vendor)
$definesFile = _PS_ROOT_DIR_ . '/config/defines.inc.php';
if (file_exists($definesFile)) {
    @require_once $definesFile;
}

// 3. Construir classMap desde mirror/classes/**
$classesDir = _PS_ROOT_DIR_ . '/classes/';
$classMap = [];
if (is_dir($classesDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($classesDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if ($file->getExtension() === 'php') {
            $classMap[$file->getBasename('.php')] = $file->getPathname();
        }
    }
}

// 4. Autoloader legacy con patron Core
// Module.php define ModuleCore, no Module. Tras cargar el fichero, creamos el alias.
spl_autoload_register(function (string $class) use (&$classMap): void {
    $loaded = false;

    // Intento directo: buscar Xxx.php
    if (isset($classMap[$class])) {
        @require_once $classMap[$class];
        $loaded = true;
    }

    // Si la clase sigue sin existir, intentar cargar XxxCore y crear alias
    if (!$loaded || (!class_exists($class, false) && !interface_exists($class, false))) {
        $coreClass = $class . 'Core';
        if (isset($classMap[$coreClass])) {
            @require_once $classMap[$coreClass];
        }
        // Patron PS: el fichero Xxx.php define XxxCore; crear alias
        if (class_exists($class . 'Core', false) && !class_exists($class, false)) {
            class_alias($class . 'Core', $class);
        }
    }
}, true, true);

// 5. PSR-4 para PrestaShop\PrestaShop\* -> mirror/src/
$srcDir = _PS_ROOT_DIR_ . '/src/';
spl_autoload_register(function (string $class) use ($srcDir): void {
    $prefix = "PrestaShop\\PrestaShop\\";
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $rel  = substr($class, strlen($prefix));
        $file = $srcDir . str_replace("\\", "/", $rel) . '.php';
        if (file_exists($file)) {
            @require_once $file;
        }
    }
}, true, false);

// 6. PSR-4 para PrestaShopBundle\* -> mirror/src/PrestaShopBundle/
$srcBundleDir = _PS_ROOT_DIR_ . '/src/PrestaShopBundle/';
spl_autoload_register(function (string $class) use ($srcBundleDir): void {
    $prefix = "PrestaShopBundle\\";
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $rel  = substr($class, strlen($prefix));
        $file = $srcBundleDir . str_replace("\\", "/", $rel) . '.php';
        if (file_exists($file)) {
            @require_once $file;
        }
    }
}, true, false);