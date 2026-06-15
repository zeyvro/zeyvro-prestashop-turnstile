<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
function upgrade_module_1_0_7($module)
{
    // Crear grupo Zeyvro en PERSONALIZAR (mismo nivel que Internacional)
    $idGroup = (int) Tab::getIdFromClassName('AdminZeyvroGroup');
    if ($idGroup <= 0) {
        $idLocalization = (int) Tab::getIdFromClassName('AdminParentLocalization');
        $idSection      = 0;
        if ($idLocalization > 0) {
            $locTab    = new Tab($idLocalization);
            $idSection = (int) $locTab->id_parent;
        }

        $group             = new Tab();
        $group->active     = 1;
        $group->class_name = 'AdminZeyvroGroup';
        $group->module     = $module->name;
        $group->id_parent  = $idSection;
        $group->icon       = 'extension';
        foreach (Language::getLanguages(false) as $lang) {
            $group->name[$lang['id_lang']] = 'Zeyvro';
        }
        if (!$group->add()) {
            return false;
        }
        $idGroup = (int) $group->id;
    }

    // Mover AdminZeyvroTurnstile al grupo Zeyvro
    $idTab = (int) Tab::getIdFromClassName('AdminZeyvroTurnstile');
    if ($idTab > 0) {
        $tab = new Tab($idTab);
        if ((int) $tab->id_parent !== $idGroup) {
            $tab->id_parent = $idGroup;
            $tab->save();
        }
    } else {
        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = 'AdminZeyvroTurnstile';
        $tab->module     = $module->name;
        $tab->id_parent  = $idGroup;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Anti SPAM';
        }
        if (!$tab->add()) {
            return false;
        }
    }

    $module->clearAllCaches();
    return true;
}
