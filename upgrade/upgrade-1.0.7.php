<?php
/**
 * Zeyvro - Cloudflare Turnstile for PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    Zeyvro <admin@zeyvro.com>
 * @copyright 2026 Zeyvro
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
/*
 * Upgrade 1.0.7 — Repara la jerarquía de tabs al patrón canónico AdminZeyvroParent.
 *
 * Problema en 1.0.6: el tab se creó bajo AdminParentLocalization (Internacional) y/o
 * se usó AdminZeyvroGroup como parent en lugar de AdminZeyvroParent.
 *
 * Este script es IDEMPOTENTE: correrlo dos veces no duplica nada.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_0_7(Module $module): bool
{
    try {
        $db = Db::getInstance();

        // -- 1. Determinar id_parent correcto: IMPROVE -> AdminParentModulesSf
        $id_improve = (int) Tab::getIdFromClassName('IMPROVE');
        $id_target_parent = $id_improve
            ?: (int) Tab::getIdFromClassName('AdminParentModulesSf');

        if (!$id_target_parent) {
            PrestaShopLogger::addLog(
                'zeyvro_turnstile upgrade-1.0.7: no se encontro IMPROVE ni AdminParentModulesSf',
                3
            );

            return false;
        }

        // -- 2. Consolidar AdminZeyvroParent (puede haber 0, 1 o varios)
        $parents = $db->executeS(
            'SELECT `id_tab`, `id_parent`, `module`
             FROM `' . _DB_PREFIX_ . 'tab`
             WHERE `class_name` = "AdminZeyvroParent"
             ORDER BY `id_tab` ASC'
        );

        if (empty($parents)) {
            // No existe: crear
            $parent = new Tab();
            $parent->active = true;
            $parent->class_name = 'AdminZeyvroParent';
            $parent->name = [];
            foreach ((Language::getLanguages(true) ?: []) as $lang) {
                $parent->name[$lang['id_lang']] = 'Zeyvro';
            }
            $parent->id_parent = $id_target_parent;
            $parent->module = '';
            $parent->icon = 'tune';
            if (!$parent->add()) {
                PrestaShopLogger::addLog(
                    'zeyvro_turnstile upgrade-1.0.7: fallo al crear AdminZeyvroParent',
                    3
                );

                return false;
            }
            $id_zeyvro_parent = (int) $parent->id;
            _upgrade107CreateTabRoles('AdminZeyvroParent', $db);
        } else {
            // Usar el primero; borrar duplicados reasignando sus hijos al primero
            $canonical = array_shift($parents);
            $id_zeyvro_parent = (int) $canonical['id_tab'];

            // Reparentar hijos de duplicados al canonico y borrar duplicados
            foreach ($parents as $dup) {
                $db->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'tab`
                     SET `id_parent` = ' . $id_zeyvro_parent . '
                     WHERE `id_parent` = ' . (int) $dup['id_tab']
                );
                (new Tab((int) $dup['id_tab']))->delete();
            }

            // Normalizar id_parent y module='' en el canonico
            $db->execute(
                'UPDATE `' . _DB_PREFIX_ . 'tab`
                 SET `id_parent` = ' . $id_target_parent . ', `module` = ""
                 WHERE `id_tab` = ' . $id_zeyvro_parent
            );
        }

        // -- 3. Eliminar AdminZeyvroGroup si existe
        $id_group = (int) Tab::getIdFromClassName('AdminZeyvroGroup');
        if ($id_group) {
            // Reparentar hijos al AdminZeyvroParent canonico antes de borrar
            $db->execute(
                'UPDATE `' . _DB_PREFIX_ . 'tab`
                 SET `id_parent` = ' . $id_zeyvro_parent . '
                 WHERE `id_parent` = ' . $id_group
            );
            (new Tab($id_group))->delete();
        }

        // -- 4. Reparar/crear AdminZeyvroTurnstile
        $id_child = (int) Tab::getIdFromClassName('AdminZeyvroTurnstile');
        if ($id_child) {
            // Ya existe: asegurar id_parent correcto y nombre "Anti SPAM"
            $tab = new Tab($id_child);
            $tab->id_parent = $id_zeyvro_parent;
            $tab->module = $module->name;
            $tab->active = true;
            $tab->icon = 'verified_user';
            foreach ((Language::getLanguages(true) ?: []) as $lang) {
                $tab->name[$lang['id_lang']] = 'Anti SPAM';
            }
            $tab->save();
        } else {
            // No existe: crear
            $tab = new Tab();
            $tab->active = true;
            $tab->class_name = 'AdminZeyvroTurnstile';
            $tab->name = [];
            foreach ((Language::getLanguages(true) ?: []) as $lang) {
                $tab->name[$lang['id_lang']] = 'Anti SPAM';
            }
            $tab->id_parent = $id_zeyvro_parent;
            $tab->module = $module->name;
            $tab->icon = 'verified_user';
            $tab->add();
        }
        _upgrade107CreateTabRoles('AdminZeyvroTurnstile', $db);

        // -- 5. Cache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        @Tools::clearSmartyCache();
        @Media::clearCache();

        return true;
    } catch (Exception $e) {
        PrestaShopLogger::addLog(
            'zeyvro_turnstile upgrade-1.0.7 error: ' . $e->getMessage(),
            3, null, 'zeyvro_turnstile', 0, true
        );

        return true; // nunca WSOD — el boton Actualizar nativo queda como fallback
    }
}

function _upgrade107CreateTabRoles(string $class_name, $db): void
{
    $actions = ['CREATE', 'READ', 'UPDATE', 'DELETE'];
    $slug_prefix = 'ROLE_MOD_TAB_' . Tools::strtoupper($class_name) . '_';
    foreach ($actions as $action) {
        $slug = $slug_prefix . $action;
        $db->execute(
            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'authorization_role` (`slug`)
             VALUES ("' . pSQL($slug) . '")'
        );
        $id_role = (int) $db->getValue(
            'SELECT `id_authorization_role`
             FROM `' . _DB_PREFIX_ . 'authorization_role`
             WHERE `slug` = "' . pSQL($slug) . '"'
        );
        if ($id_role) {
            $db->execute(
                'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'access`
                 (`id_profile`, `id_authorization_role`)
                 VALUES (1, ' . $id_role . ')'
            );
        }
    }
}
