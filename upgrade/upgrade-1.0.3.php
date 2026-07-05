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
    $idTab = (int) Tab::getIdFromClassName('AdminZeyvroTurnstile');
    if ($idTab <= 0) {
        // No existe la tab → install() la creará al final del flujo.
        return true;
    }

    $idParent = (int) Tab::getIdFromClassName('AdminParentCustomerThreads');
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
