<?php
/**
 * @author  Zeyvro <hola@zeyvro.com>
 * @license MIT
 * @link    https://zeyvro.com
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_3($module)
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
