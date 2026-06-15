<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_1_0_7($module)
{
    // Mover/renombrar tab a PERSONALIZAR (mismo nivel que Internacional), nombre "Zeyvro"
    $idLocalization = (int) Tab::getIdFromClassName('AdminParentLocalization');
    $idSection      = 0;
    if ($idLocalization > 0) {
        $locTab    = new Tab($idLocalization);
        $idSection = (int) $locTab->id_parent;
    }

    // Borrar grupo AdminZeyvroGroup si quedó de versiones anteriores
    $idGroup = (int) Tab::getIdFromClassName('AdminZeyvroGroup');
    if ($idGroup > 0) {
        (new Tab($idGroup))->delete();
    }

    $idTab = (int) Tab::getIdFromClassName('AdminZeyvroTurnstile');
    if ($idTab > 0) {
        $tab            = new Tab($idTab);
        $tab->id_parent = $idSection;
        $tab->icon      = 'extension';
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Zeyvro';
        }
        $tab->save();
    } else {
        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = 'AdminZeyvroTurnstile';
        $tab->module     = $module->name;
        $tab->id_parent  = $idSection;
        $tab->icon       = 'extension';
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Zeyvro';
        }
        $tab->add();
    }

    $module->clearAllCaches();
    return true;
}
