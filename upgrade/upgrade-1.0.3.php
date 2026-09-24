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

function upgrade_module_1_0_3(Module $module): bool
{
    $idTab = _upgrade103TabId('AdminZeyvroTurnstile');
    if ($idTab <= 0) {
        // No existe la tab → install() la creará al final del flujo.
        return true;
    }

    $idParent = _upgrade103TabId('AdminParentCustomerThreads');
    if ($idParent <= 0) {
        // Menú padre no encontrado — no romper, dejar como esté.
        return true;
    }

    $tab = new Tab($idTab);
    $tab->id_parent = $idParent;
    $tab->active = true;
    foreach (Language::getLanguages(false) as $lang) {
        $tab->name[$lang['id_lang']] = 'Anti SPAM';
    }

    return (bool) $tab->update();
}

/**
 * id_tab por class_name, 0 si no existe (como ZeyvroModuleTrait::zvTabIdFromClassName; sin
 * el método estático de Tab que busca por class_name, deprecado desde PrestaShop 1.7.1.0).
 */
function _upgrade103TabId(string $class_name): int
{
    return (int) Db::getInstance()->getValue(
        'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "' . pSQL($class_name) . '"'
    );
}
