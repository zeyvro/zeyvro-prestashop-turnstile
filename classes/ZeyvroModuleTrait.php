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
if (!defined('_PS_VERSION_')) {
    exit;
}

// Guard: varios módulos Zeyvro copian este fichero; PHP solo puede declarar
// el trait una vez por proceso, así que el segundo require_once da Fatal Error
// sin esta protección.
if (!trait_exists('ZeyvroModuleTrait', false)) :
    trait ZeyvroModuleTrait
    {
        /* =========================================================================
         * TABS — ensureTabs() idempotente, sin early-return
         * ======================================================================= */

        /**
         * Crea o normaliza el árbol de tabs Zeyvro.
         * Idempotente: correr 2× no duplica nada ni deja estado inconsistente.
         * NUNCA usa early-return en el child — siempre normaliza el estado actual.
         */
        public function ensureTabs(): bool
        {
            try {
                $id_parent = $this->zvEnsureParentTab();
                if (!$id_parent) {
                    return false;
                }
                $this->zvEnsureChildTab($id_parent);
                $this->zvClearSf2MenuCache();

                return true;
            } catch (Exception $e) {
                PrestaShopLogger::addLog(
                    '[ZeyvroModuleTrait] ensureTabs error: ' . $e->getMessage(),
                    3, null, $this->name, 0, true
                );

                return false;
            }
        }

        /**
         * Crea o consolida AdminZeyvroParent bajo IMPROVE.
         * Si hay duplicados, reasigna sus hijos al canónico y elimina los extras.
         *
         * @return int id_tab del parent canónico, 0 en caso de error
         */
        private function zvEnsureParentTab(): int
        {
            $db = Db::getInstance();
            $rows = $db->executeS(
                'SELECT `id_tab`, `module`
             FROM `' . _DB_PREFIX_ . 'tab`
             WHERE `class_name` = "AdminZeyvroParent"
             ORDER BY `id_tab` ASC'
            );
            $rows = is_array($rows) ? $rows : [];

            $id_improve = (int) Tab::getIdFromClassName('IMPROVE')
                ?: (int) Tab::getIdFromClassName('AdminParentModulesSf');

            if (empty($rows)) {
                // Crear parent
                $parent = new Tab();
                $parent->active = true;
                $parent->class_name = 'AdminZeyvroParent';
                $parent->module = '';   // compartido — NUNCA el nombre de un módulo concreto
                $parent->id_parent = $id_improve;
                $parent->icon = 'tune';
                $parent->name = [];
                foreach ((Language::getLanguages(true) ?: []) as $lang) {
                    $parent->name[$lang['id_lang']] = 'Zeyvro';
                }
                if (!$parent->add()) {
                    return 0;
                }
                $id_parent = (int) $parent->id;
            } else {
                // Usar el primero como canónico; reparar duplicados
                $canonical = array_shift($rows);
                $id_parent = (int) $canonical['id_tab'];

                foreach ($rows as $dup) {
                    $db->execute(
                        'UPDATE `' . _DB_PREFIX_ . 'tab`
                     SET `id_parent` = ' . $id_parent . '
                     WHERE `id_parent` = ' . (int) $dup['id_tab']
                    );
                    (new Tab((int) $dup['id_tab']))->delete();
                }

                // Normalizar: id_parent correcto + module=''
                $db->execute(
                    'UPDATE `' . _DB_PREFIX_ . 'tab`
                 SET `id_parent` = ' . $id_improve . ', `module` = ""
                 WHERE `id_tab` = ' . $id_parent
                );
            }

            $this->zvCreateTabRoles('AdminZeyvroParent');

            return $id_parent;
        }

        /**
         * Crea o normaliza el tab hijo del módulo.
         * PROHIBIDO early-return: siempre se normaliza el estado existente.
         */
        private function zvEnsureChildTab(int $id_parent): void
        {
            $id_child = (int) Tab::getIdFromClassName(static::ZV_TAB_CLASS);
            if ($id_child) {
                // Tab existe — normalizar SIEMPRE (puede estar roto: id_parent=0, active=0...)
                $tab = new Tab($id_child);
                $tab->id_parent = $id_parent;
                $tab->module = $this->name;
                $tab->active = true;
                $tab->icon = static::ZV_TAB_ICON;
                $tab->name = [];
                foreach ((Language::getLanguages(true) ?: []) as $lang) {
                    $tab->name[$lang['id_lang']] = static::ZV_TAB_NAME;
                }
                $tab->save();
            } else {
                $tab = new Tab();
                $tab->active = true;
                $tab->class_name = static::ZV_TAB_CLASS;
                $tab->module = $this->name;
                $tab->id_parent = $id_parent;
                $tab->icon = static::ZV_TAB_ICON;
                $tab->name = [];
                foreach ((Language::getLanguages(true) ?: []) as $lang) {
                    $tab->name[$lang['id_lang']] = static::ZV_TAB_NAME;
                }
                $tab->add();
            }
            $this->zvCreateTabRoles(static::ZV_TAB_CLASS);
        }

        /**
         * Borra el tab hijo; borra el parent solo si no quedan otros hijos.
         */
        protected function zvUninstallTab(): void
        {
            $id = (int) Tab::getIdFromClassName(static::ZV_TAB_CLASS);
            if ($id) {
                (new Tab($id))->delete();
            }
            $id_parent = (int) Tab::getIdFromClassName('AdminZeyvroParent');
            if ($id_parent) {
                $children = (int) Db::getInstance()->getValue(
                    'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'tab`
                 WHERE `id_parent` = ' . $id_parent
                );
                if ($children === 0) {
                    (new Tab($id_parent))->delete();
                }
            }
        }

        private function zvCreateTabRoles(string $class_name): void
        {
            $db = Db::getInstance();
            $prefix = 'ROLE_MOD_TAB_' . Tools::strtoupper($class_name) . '_';
            foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
                $slug = $prefix . $action;
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

        private function zvClearSf2MenuCache(): void
        {
            try {
                if (method_exists('Tools', 'clearSf2Cache')) {
                    @Tools::clearSf2Cache();
                }
            } catch (Throwable $t) {
                // best-effort
            }
        }

        /* =========================================================================
         * CACHÉ §2.1
         * ======================================================================= */

        public function clearAllCaches(): void
        {
            try {
                if (function_exists('opcache_reset')) {
                    @opcache_reset();
                }
                @Tools::clearSmartyCache();
                @Media::clearCache();
                if (class_exists('PrestaShopAutoload')) {
                    @PrestaShopAutoload::getInstance()->generateIndex();
                }
            } catch (Throwable $t) {
                // best-effort — nunca rompe install/upgrade
            }
        }

        /* =========================================================================
         * AUTO-REPARACIÓN — hookActionAdminControllerSetMedia
         * ======================================================================= */

        /**
         * Se dispara en cada carga del BO.
         * Guarded por flag: solo ejecuta si el schema de tabs no coincide.
         * Garantiza que, tras subir el ZIP, los tabs se reparan en la siguiente
         * carga sin necesidad de desinstalar ni limpiar caché manualmente.
         */
        public function hookActionAdminControllerSetMedia($params): void
        {
            if (Configuration::get(static::ZV_SCHEMA_KEY) === static::ZV_SCHEMA_TABV) {
                return;
            }
            $this->ensureTabs();
            Configuration::updateValue(static::ZV_SCHEMA_KEY, static::ZV_SCHEMA_TABV);
        }

        /* =========================================================================
         * INSTALL / UNINSTALL BASE
         * ======================================================================= */

        /**
         * Parte común del install: registra el hook de auto-reparación,
         * crea/normaliza tabs, limpia cachés.
         */
        protected function installBase(): bool
        {
            $ok = $this->registerHook('actionAdminControllerSetMedia')
                && $this->ensureTabs();
            if ($ok) {
                $this->clearAllCaches();
            }

            return $ok;
        }

        /**
         * Parte común del uninstall: limpia tab y hook de auto-reparación.
         * PRESERVATIVO: NO borra tablas, configuración ni datos.
         */
        protected function uninstallBase(): void
        {
            $this->zvUninstallTab();
            $this->unregisterHook('actionAdminControllerSetMedia');
        }
    }
endif; // trait_exists guard
