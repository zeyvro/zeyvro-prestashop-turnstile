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
    ->exclude(['vendor', 'node_modules', '_dist'])
    ->name('*.php')
    ->notName('*.phtml')
    // classes/ZeyvroModuleTrait.php es copia VERBATIM del canon _shared/
    // ZeyvroModuleTrait.php (check 43 verify-module-build.py exige byte-
    // identidad); cs-fixer reindenta su bloque if(...):...endif; y lo
    // desincroniza. Fuera de alcance (hallazgo 2026-07-05).
    ->notPath('classes/ZeyvroModuleTrait.php');

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
        // phpdoc_to_comment: convierte /** no-estructurales a /* ordinario.
        // PHP CS Fixer 3.x preserva el primer /** (file-header/licencia) de cada
        // fichero; solo convierte /** secundarios no estructurales (ej. descripción
        // en upgrade/). Licenses sigue en 0. Validado Validator real 2026-06-23.
        'phpdoc_to_comment' => true,
        //
        // ── BARRERAS IRREDUCIBLES — contradicciones del propio Validator PS ──────
        //
        // blank_line_after_opening_tag=false OBLIGATORIO.
        // El Validator exige "There must be no blank lines before the file comment"
        // (cabecera /** licencia */ pegada a <?php). Habilitar esta regla rompió
        // Licenses 0→23 en turnstile 3740522 (2026-06-23). Standards residuales
        // por blank_line son IRREDUCIBLES — contradicción interna del Validator.
        'blank_line_after_opening_tag' => false,
        //
        // no_alternative_syntax=false OBLIGATORIO.
        // Convertiría if/endif a {}. El parser estático del Validator PS detecta
        // `trait X {}` solo dentro de if(...):...endif; en ZeyvroModuleTrait.php.
        // Habilitarla rompe Requirements. Ver REGLAS-DURAS §trampas.
        'no_alternative_syntax' => false,
    ])
    ->setFinder($finder);
