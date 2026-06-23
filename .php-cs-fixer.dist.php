<?php
/**
 * php-cs-fixer config — zeyvro_turnstile
 * Ruleset: PrestaShop official (prestashop/php-dev-tools CsFixer\Config).
 * Equivalent to @Symfony + overrides documented in prestashop/php-dev-tools src/CsFixer/Config.php.
 *
 * Run: php-cs-fixer fix --config .php-cs-fixer.dist.php
 *      php-cs-fixer fix --config .php-cs-fixer.dist.php --dry-run --diff
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', '_dist', 'translations'])
    ->name('*.php')
    ->notName('*.phtml');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => ['space' => 'single'],
        'error_suppression' => [
            'mute_deprecation_error' => false,
            'noise_remaining_usages' => false,
            'noise_remaining_usages_exclude' => [],
        ],
        'function_to_constant' => false,
        'visibility_required' => ['elements' => ['property', 'method']],
        'no_alias_functions' => false,
        'phpdoc_summary' => false,
        'phpdoc_align' => ['align' => 'left'],
        'protected_to_private' => false,
        'psr_autoloading' => false,
        'self_accessor' => false,
        'yoda_style' => false,
        'non_printable_character' => true,
        'no_superfluous_phpdoc_tags' => false,
        // ── Reglas habilitadas tras verificación con Validator real (v1.1.2) ──────
        //
        // blank_line_after_opening_tag: añade blank line tras <?php en todos los
        // ficheros. PHP CS Fixer 3.x trata el primer /** del fichero como file-header
        // y NO lo desplaza; el check Licenses sigue en 0.
        // Validator PS requiere esta regla: Standards=4 sin ella.
        'blank_line_after_opening_tag' => true,
        //
        // phpdoc_to_comment: convierte /** no-estructurales a /* ordinario.
        // PHP CS Fixer 3.x preserva el primer /** (file-header) como /** — los
        // index.php de seguridad y los headers de licencia quedan intactos.
        // Solo convierte /** secundarios (ej. comentarios de descripción en upgrade/).
        'phpdoc_to_comment' => true,
        //
        // ── BARRERA IRREDUCIBLE — NO habilitar: ─────────────────────────────────
        // no_alternative_syntax: convertiría if/endif a {}. El parser estático del
        // Validator PS detecta `trait X {}` solo dentro de if(...):...endif; en
        // ZeyvroModuleTrait.php. Rompe el check Requirements. Ver REGLAS-DURAS §trampas.
        'no_alternative_syntax' => false,
    ])
    ->setFinder($finder);
