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
        // ⚠ BARRERAS IRREDUCIBLES — NO habilitar ninguna de las siguientes:
        // Son contradicciones internas del Validator PS; NO arreglar. Standards no es auto-decline.
        //
        // 1) blank_line_after_opening_tag: añade una línea en blanco entre <?php y el
        //    docblock de licencia. El check "Licenses" del Validator exige que NO haya
        //    línea en blanco ahí ("no blank lines before file comment"). Habilitarla
        //    produce regresiones Licenses.
        //
        // 2) no_alternative_syntax: convierte if/endif a llaves {}. El parser estático
        //    del Validator PS no detecta `trait X {}` dentro de un if(){} estándar, pero
        //    sí lo detecta dentro de if(...):...endif;. Convertir el guard de
        //    ZeyvroModuleTrait.php rompería el check Requirements. Ver REGLAS-DURAS §trampas.
        //
        // 3) phpdoc_to_comment: convierte bloques /** sin @param/@return a /* ordinario.
        //    El Validator PS exige /** (docblock) para el file comment de los index.php
        //    de seguridad ("You must use /** style comments for a file comment").
        'blank_line_after_opening_tag' => false,
        'no_alternative_syntax' => false,
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder);
