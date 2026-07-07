<?php
/**
 * Zeyvro - Turnstile for PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Zeyvro Commercial License (EULA)
 * bundled with this package in the file LICENSE.
 * Unauthorized copying or redistribution is prohibited.
 *
 * @author    Zeyvro <admin@zeyvro.com>
 * @copyright 2026 Zeyvro
 * @license   Commercial license - Zeyvro EULA
 */

use PhpCsFixer\Finder;
use PrestaShop\CodingStandards\CsFixer\Config as PrestaShopConfig;

// CONFIG ANCLADA — validada VERDE en el Validator oficial (run 3744526):
// Standards = 0 y Licencias = verde AL MISMO TIEMPO.
// Reglas: clase oficial + UN unico override (blank_line_after_opening_tag=false).
// PROHIBIDO: anadir mas overrides, y PROHIBIDO excluir el trait del Finder
// (excluirlo dispara Standards a 462 — comprobado en run 3744545).
$config = new class() extends PrestaShopConfig {
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'blank_line_after_opening_tag' => false,
        ]);
    }
};

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', '_dist'])
    ->name('*.php')
    ->notName('*.phtml');
// SIN ->notPath('classes/ZeyvroModuleTrait.php'): el trait DEBE pasar por cs-fixer.

return $config->setFinder($finder);
