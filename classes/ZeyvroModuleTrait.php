<?php
/**
 * ZeyvroModuleTrait — Base común compartida por todos los módulos Zeyvro.
 *
 * FUENTE ÚNICA: modulos-prestashop/_shared/ZeyvroModuleTrait.php
 * Se copia VERBATIM en classes/ZeyvroModuleTrait.php de cada módulo.
 * Nunca se modifica por módulo — los cambios van en la fuente y se propagan.
 *
 * Constantes que el módulo DEBE declarar (como `const` de clase):
 *   ZV_TAB_CLASS   — class_name del tab hijo  (ej. 'AdminZeyvroTurnstile')
 *   ZV_TAB_NAME    — nombre visible en el menú (ej. 'Anti SPAM')
 *   ZV_TAB_ICON    — icono Material Design     (ej. 'verified_user')
 *   ZV_ADS_VARIANT — 'free' | 'paid'
 *   ZV_SCHEMA_TABV — versión del schema de tabs (ej. 'A'); cambiar solo si la
 *                    estructura de tabs cambia para forzar re-reparación.
 *   ZV_SCHEMA_KEY  — config key del flag de auto-reparación
 *                    (ej. 'ZEYVROTURNSTILE_TABV')
 *
 * Constante OPCIONAL (fallback al valor por defecto si no se declara):
 *   PROMO_REMOTE_ENABLED — true (def.) habilita el fetch del feed remoto de promos;
 *                          false en builds para Addons/-nolic (CERO llamadas de red).
 *
 * @author    Zeyvro
 * @copyright 2026 Zeyvro
 * @license   MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Guard: varios módulos Zeyvro copian este fichero; PHP solo puede declarar
// el trait una vez por proceso, así que el segundo require_once da Fatal Error
// sin esta protección.
//
// ⚠ BARRERA IRREDUCIBLE — NO convertir a llaves {}/endif:
// La sintaxis alternativa (if:...endif;) es OBLIGATORIA aquí. El parser estático
// del Validator PS no detecta `trait X { }` dentro de un bloque `if () { }` en
// el scope de fichero, pero SÍ lo detecta dentro de `if (...) : ... endif;`.
// php-cs-fixer @Symfony incluye `no_alternative_syntax` que lo convertiría a
// llaves — por eso está excluida en .php-cs-fixer.dist.php. Es una contradicción
// documentada del Validator; ver REGLAS-DURAS-deploy-git-modulos.md §trampas.
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
        } catch (\Exception $e) {
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
        $db   = Db::getInstance();
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
            $parent             = new Tab();
            $parent->active     = true;
            $parent->class_name = 'AdminZeyvroParent';
            $parent->module     = '';   // compartido — NUNCA el nombre de un módulo concreto
            $parent->id_parent  = $id_improve;
            $parent->icon       = 'tune';
            $parent->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
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
            $tab            = new Tab($id_child);
            $tab->id_parent = $id_parent;
            $tab->module    = $this->name;
            $tab->active    = true;
            $tab->icon      = static::ZV_TAB_ICON;
            $tab->name      = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = static::ZV_TAB_NAME;
            }
            $tab->save();
        } else {
            $tab             = new Tab();
            $tab->active     = true;
            $tab->class_name = static::ZV_TAB_CLASS;
            $tab->module     = $this->name;
            $tab->id_parent  = $id_parent;
            $tab->icon       = static::ZV_TAB_ICON;
            $tab->name       = [];
            foreach (Language::getLanguages(true) as $lang) {
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
        $db     = Db::getInstance();
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
        } catch (\Throwable $t) {
            // best-effort
        }
    }

    /* =========================================================================
     * CACHÉ §2.1
     * ======================================================================= */

    public function clearAllCaches(): void
    {
        try {
            @Tools::clearSmartyCache();
            @Media::clearCache();
        } catch (\Throwable $t) {
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

    /* =========================================================================
     * i18n HELPERS
     * ======================================================================= */

    /**
     * Devuelve el ISO (lowercase) del idioma activo del empleado del BO.
     * Fallback: context->language, luego 'es'.
     */
    private function zvGetEmployeeIso(): string
    {
        try {
            if (isset($this->context->employee->id_lang)) {
                $iso = Language::getIsoById((int) $this->context->employee->id_lang);
                if ($iso) {
                    return strtolower((string) $iso);
                }
            }
            if (isset($this->context->language->iso_code)) {
                return strtolower((string) $this->context->language->iso_code);
            }
        } catch (\Throwable $t) {
            // best-effort
        }
        return 'es';
    }

    /**
     * Resuelve un campo del feed que puede ser string o mapa {iso => string}.
     * Prioridad: $iso → 'en' → 'es' → primer valor.
     * Retrocompatible con feeds que envian strings directos.
     *
     * @param string|array $field
     */
    private function zvLocalize($field, string $iso): string
    {
        if (is_array($field)) {
            if (isset($field[$iso])) {
                return (string) $field[$iso];
            }
            if (isset($field['en'])) {
                return (string) $field['en'];
            }
            if (isset($field['es'])) {
                return (string) $field['es'];
            }
            $first = reset($field);
            return is_string($first) ? $first : '';
        }
        return (string) $field;
    }

    /* =========================================================================
     * PUBLICIDAD §5 — dinámico con feed remoto, caché global y fallback genérico
     * ======================================================================= */

    /**
     * Renderiza el bloque de publicidad Zeyvro.
     *
     * Si PROMO_REMOTE_ENABLED === false (build Addons/-nolic): banner genérico sin
     * precios ni llamadas de red.
     * Si true (por defecto): intenta el feed remoto (caché 12h en Configuration global);
     * fallback al banner genérico si el fetch falla o el JSON no es válido.
     *
     * El módulo actual se auto-excluye de las cartas (campo `module` del feed).
     */
    public function renderZeyvroAds(): string
    {
        $promoEnabled = defined(static::class . '::PROMO_REMOTE_ENABLED')
            ? (bool) constant(static::class . '::PROMO_REMOTE_ENABLED')
            : true;

        $iso = $this->zvGetEmployeeIso();

        if (!$promoEnabled) {
            return $this->zvRenderGenericBanner($iso);
        }

        $cards = $this->zvLoadPromoCards($iso);

        return $cards !== null
            ? $this->zvBuildAdsHtml($cards, $iso)
            : $this->zvRenderGenericBanner($iso);
    }

    /**
     * Carga cartas del feed remoto con caché global de 12h en Configuration.
     * Retorna array de cartas localizadas al $iso o null para activar el banner genérico.
     */
    private function zvLoadPromoCards(string $iso): ?array
    {
        $currentMod = isset($this->name) ? (string) $this->name : '';
        $ttl        = 12 * 3600;
        $now        = time();

        $cacheTs   = (int) Configuration::get('ZEYVRO_PROMO_FEED_TS');
        $cacheJson = (string) Configuration::get('ZEYVRO_PROMO_FEED_CACHE');

        $data = null;

        // 1. Cache fresca (< 12h)?
        if ($cacheTs > 0 && ($now - $cacheTs) < $ttl && $cacheJson !== '') {
            $decoded = json_decode($cacheJson, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        // 2. Cache caducada o inexistente -> intentar fetch
        if ($data === null) {
            $fetched = $this->zvFetchPromoFeed();
            if ($fetched !== null) {
                $data = $fetched;
                Configuration::updateValue('ZEYVRO_PROMO_FEED_CACHE', json_encode($data));
                Configuration::updateValue('ZEYVRO_PROMO_FEED_TS', (string) $now);
            } elseif ($cacheJson !== '') {
                // Fetch fallido -> usar cache vieja antes que banner generico
                $decoded = json_decode($cacheJson, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }

        if (!is_array($data) || !isset($data['cards']) || !is_array($data['cards'])) {
            return null;
        }

        // Validar y filtrar cartas; cada campo localizado al idioma del empleado
        $cards = [];
        foreach ($data['cards'] as $card) {
            if (!is_array($card)) {
                continue;
            }
            // Auto-excluir modulo actual
            if (isset($card['module'])
                && $card['module'] !== null
                && (string) $card['module'] === $currentMod
            ) {
                continue;
            }
            // Campos obligatorios
            if (!isset($card['name'], $card['desc'], $card['url'])) {
                continue;
            }
            // Resolver url (puede ser {es,en} o string directo)
            $url = $this->zvLocalize($card['url'], $iso);
            // Whitelist URL: debe empezar por https://
            if (strpos($url, 'https://') !== 0) {
                continue;
            }
            // Longitudes maximas; cada campo localizado
            $cards[] = [
                'name'  => mb_substr($this->zvLocalize($card['name'], $iso), 0, 60),
                'desc'  => mb_substr($this->zvLocalize($card['desc'], $iso), 0, 180),
                'price' => isset($card['price'])
                    ? mb_substr($this->zvLocalize($card['price'], $iso), 0, 20)
                    : '',
                'url'   => mb_substr($url, 0, 300),
            ];
            if (count($cards) >= 6) {
                break;
            }
        }

        return empty($cards) ? null : $cards;
    }

    /**
     * Fetch cURL del feed remoto. CONNECTTIMEOUT 3s / TIMEOUT 4s / SSL_VERIFYPEER true.
     * Devuelve el array JSON decodificado o null si falla.
     */
    private function zvFetchPromoFeed(): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init('https://zeyvro.com/promo/modules.json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS      => 0,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $http !== 200) {
            return null;
        }
        $data = json_decode((string) $body, true);

        return (is_array($data) && json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }

    /**
     * Banner genérico: sin precios, sin cartas, CERO red.
     * Textos traducidos via $this->l() al idioma del empleado del BO.
     * Mostrado cuando PROMO_REMOTE_ENABLED=false o el feed no esta disponible.
     */
    private function zvRenderGenericBanner(string $iso): string
    {
        $mod = isset($this->name) ? (string) $this->name : 'zeyvro_module';
        $utm = 'utm_source=module&amp;utm_medium=bo&amp;utm_campaign='
            . htmlspecialchars($mod, ENT_QUOTES);

        $baseUrl  = ($iso === 'en')
            ? 'https://zeyvro.com/en/modules/'
            : 'https://zeyvro.com/modulos/';

        $tagline  = $this->l(
            'Premium PrestaShop 8 modules — no subscriptions, no lock-in. Clean code, one-time payment.'
        );
        $btnLabel = $this->l('Discover them →');

        return '<div class="panel zeyvro-ads-panel" style="'
            . 'border-top:2px solid #6C63FF;margin-top:24px;padding:16px;'
            . 'background:#fafaff;border-radius:0 0 4px 4px;font-family:sans-serif;">'
            . '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">'
            . '<strong style="font-size:18px;color:#6C63FF;">Zeyvro</strong>'
            . '<span style="color:#666;font-size:12px;">'
            . '&middot; Premium PS8 modules</span>'
            . '</div>'
            . '<p style="color:#555;font-size:13px;margin:0 0 10px;">'
            . htmlspecialchars($tagline, ENT_QUOTES)
            . '</p>'
            . '<a href="' . $baseUrl . '?' . $utm . '" '
            . 'target="_blank" rel="noopener noreferrer" '
            . 'style="display:inline-block;background:#6C63FF;color:#fff;padding:6px 14px;'
            . 'border-radius:4px;font-size:13px;text-decoration:none;">'
            . htmlspecialchars($btnLabel, ENT_QUOTES) . '</a>'
            . '</div>';
    }

    /**
     * Monta el HTML del panel de anuncios con las cartas dinámicas del feed.
     * Las cartas ya están localizadas al $iso por zvLoadPromoCards().
     */
    private function zvBuildAdsHtml(array $cards, string $iso): string
    {
        $mod = isset($this->name) ? (string) $this->name : 'zeyvro_module';
        $utm = 'utm_source=module&utm_medium=bo&utm_campaign=' . rawurlencode($mod);

        $viewLabel = ($iso === 'en') ? 'View →' : 'Ver →';

        $html = '<div class="panel zeyvro-ads-panel" style="'
            . 'border-top:2px solid #6C63FF;margin-top:24px;padding:16px;'
            . 'background:#fafaff;border-radius:0 0 4px 4px;font-family:sans-serif;">'
            . '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">'
            . '<strong style="font-size:18px;color:#6C63FF;">Zeyvro</strong>'
            . '<span style="color:#666;font-size:12px;">'
            . '&middot; Premium PS8 modules</span>'
            . '</div>'
            . '<p style="color:#555;font-size:13px;margin:0 0 6px;">'
            . 'Clean code &middot; No subscriptions &middot; Real support.'
            . '</p>'
            . '<a href="' . htmlspecialchars('https://zeyvro.com?' . $utm, ENT_QUOTES) . '" '
            . 'target="_blank" rel="noopener noreferrer" '
            . 'style="color:#6C63FF;font-size:13px;text-decoration:none;">zeyvro.com &rarr;</a>'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;">';

        foreach ($cards as $card) {
            $sep     = strpos($card['url'], '?') !== false ? '&' : '?';
            $cardUrl = htmlspecialchars($card['url'] . $sep . $utm, ENT_QUOTES);
            $price   = $card['price'];

            $html .= '<div style="border:1px solid #ddd;border-radius:5px;padding:12px;'
                . 'min-width:160px;flex:1;max-width:200px;background:#fff;">'
                . '<div style="font-weight:600;font-size:12px;color:#0F0F14;margin-bottom:4px;">'
                . htmlspecialchars($card['name'], ENT_QUOTES) . '</div>'
                . '<div style="font-size:11px;color:#666;margin-bottom:8px;line-height:1.4;">'
                . htmlspecialchars($card['desc'], ENT_QUOTES) . '</div>'
                . '<div style="display:flex;justify-content:space-between;align-items:center;">'
                . ($price !== ''
                    ? '<span style="font-weight:700;color:#6C63FF;font-size:12px;">'
                      . htmlspecialchars($price, ENT_QUOTES) . '</span>'
                    : '<span></span>')
                . '<a href="' . $cardUrl . '" target="_blank" rel="noopener noreferrer" '
                . 'style="background:#6C63FF;color:#fff;padding:3px 8px;border-radius:3px;'
                . 'font-size:11px;text-decoration:none;">'
                . htmlspecialchars($viewLabel, ENT_QUOTES) . '</a>'
                . '</div></div>';
        }

        return $html . '</div></div>';
    }

}
endif; // trait_exists guard
