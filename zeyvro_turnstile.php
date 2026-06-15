<?php
/**
 * Zeyvro — Turnstile Anti-Spam
 * Cloudflare Turnstile en el formulario de contacto de PrestaShop 8.
 *
 * @author  Zeyvro
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Zeyvro_Turnstile extends Module
{
    const CONFIG_KEYS = [
        'ZEYVRO_TURNSTILE_ENABLED',
        'ZEYVRO_TURNSTILE_SITE_KEY',
        'ZEYVRO_TURNSTILE_SECRET_KEY',
        'ZEYVRO_TURNSTILE_MODE',
        'ZEYVRO_TURNSTILE_ACTION_ON_FAIL',
        'ZEYVRO_TURNSTILE_LOG_ENABLED',
        'ZEYVRO_TURNSTILE_API_TIMEOUT',
    ];

    public function __construct()
    {
        $this->name          = 'zeyvro_turnstile';
        $this->tab           = 'other';
        $this->version       = '1.0.6';
        $this->author        = 'Zeyvro';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap     = true;

        parent::__construct();

        $this->displayName = $this->l('Zeyvro Turnstile');
        $this->description = $this->l('Anti-spam protection with Cloudflare Turnstile on the PrestaShop contact form.');

        // §7.1 — Auto-upgrade al subir ZIP por BO
        if (defined('_PS_ADMIN_DIR_') && !defined('ZEYVROTURNSTILE_UPGRADING')) {
            $this->runAutoUpgrade();
        }
    }

    /* =====================================================================
     * INSTALL / UNINSTALL
     * =================================================================== */

    public function install(): bool
    {
        $ok = parent::install()
            && $this->installTab()
            && $this->installSql()
            && $this->installConfig()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia');
        if ($ok) {
            $this->clearAllCaches();
        }
        return $ok;
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->uninstallSql()
            && $this->uninstallConfig();
    }

    private function installTab(): bool
    {
        // Idempotente: si el tab ya existe no crear duplicado.
        if ((int) Tab::getIdFromClassName('AdminZeyvroTurnstile') > 0) {
            return true;
        }

        $idParent = (int) Tab::getIdFromClassName('AdminParentCustomerThreads');
        if ($idParent <= 0) {
            $idParent = -1;
        }

        $tab             = new Tab();
        $tab->active     = 1;
        $tab->class_name = 'AdminZeyvroTurnstile';
        $tab->module     = $this->name;
        $tab->id_parent  = $idParent;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = 'Anti SPAM';
        }
        if (!$tab->add()) {
            return false;
        }
        $this->grantTabPermissions('AdminZeyvroTurnstile');
        return true;
    }

    private function grantTabPermissions(string $className): void
    {
        $db     = Db::getInstance();
        $ucName = strtoupper($className);

        foreach (['READ', 'UPDATE', 'CREATE', 'DELETE', 'VIEW', 'ADD', 'EDIT'] as $sfx) {
            $db->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`)
                 VALUES ('" . pSQL($className . '_' . $sfx) . "')"
            );
        }
        foreach (['READ', 'CREATE', 'UPDATE', 'DELETE'] as $sfx) {
            $db->execute(
                "INSERT IGNORE INTO `" . _DB_PREFIX_ . "authorization_role` (`slug`)
                 VALUES ('" . pSQL('ROLE_MOD_TAB_' . $ucName . '_' . $sfx) . "')"
            );
        }
        $db->execute("
            INSERT IGNORE INTO `" . _DB_PREFIX_ . "access` (`id_profile`, `id_authorization_role`)
            SELECT p.id_profile, r.id_authorization_role
            FROM `" . _DB_PREFIX_ . "profile` p
            CROSS JOIN `" . _DB_PREFIX_ . "authorization_role` r
            WHERE r.slug LIKE '" . pSQL($className) . "_%'
               OR r.slug LIKE 'ROLE_MOD_TAB_" . pSQL($ucName) . "_%'
        ");
    }

    private function uninstallTab(): bool
    {
        $id = (int) Tab::getIdFromClassName('AdminZeyvroTurnstile');
        if ($id) {
            (new Tab($id))->delete();
        }
        return true;
    }

    private function installSql(): bool
    {
        return Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "zeyvro_turnstile_log` (
                `id_log`      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip`          VARCHAR(45)  NOT NULL DEFAULT '',
                `user_agent`  VARCHAR(255) NOT NULL DEFAULT '',
                `date_add`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `success`     TINYINT(1)   NOT NULL DEFAULT 0,
                `score`       DECIMAL(4,2) NULL,
                `error_codes` TEXT         NULL,
                PRIMARY KEY (`id_log`),
                KEY `idx_date` (`date_add`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function uninstallSql(): bool
    {
        return Db::getInstance()->execute(
            "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "zeyvro_turnstile_log`"
        );
    }

    private function installConfig(): bool
    {
        $defaults = [
            'ZEYVRO_TURNSTILE_ENABLED'       => 0,
            'ZEYVRO_TURNSTILE_SITE_KEY'       => '',
            'ZEYVRO_TURNSTILE_SECRET_KEY'     => '',
            'ZEYVRO_TURNSTILE_MODE'           => 'managed',
            'ZEYVRO_TURNSTILE_ACTION_ON_FAIL' => 'block',
            'ZEYVRO_TURNSTILE_LOG_ENABLED'    => 1,
            'ZEYVRO_TURNSTILE_API_TIMEOUT'    => 5,
        ];
        foreach ($defaults as $k => $v) {
            Configuration::updateValue($k, $v);
        }
        return true;
    }

    private function uninstallConfig(): bool
    {
        foreach (self::CONFIG_KEYS as $k) {
            Configuration::deleteByName($k);
        }
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminZeyvroTurnstile')
        );
    }

    /* =====================================================================
     * HOOKS
     * =================================================================== */

    /**
     * Carga el script de la API de Turnstile solo en la página de contacto.
     * Se emite dentro de <head> para mayor rendimiento.
     */
    public function hookDisplayHeader($params)
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return '';
        }
        $phpSelf = isset($this->context->controller->php_self)
            ? $this->context->controller->php_self
            : Tools::getValue('controller');
        if ($phpSelf !== 'contact') {
            return '';
        }
        if (empty(Configuration::get('ZEYVRO_TURNSTILE_SITE_KEY'))) {
            return '';
        }
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    /**
     * Inyecta el widget Turnstile en el formulario de contacto vía JS.
     * Usa displayBeforeBodyClosingTag para asegurar que el DOM del form ya existe.
     */
    public function hookDisplayBeforeBodyClosingTag($params)
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return '';
        }
        $phpSelf = isset($this->context->controller->php_self)
            ? $this->context->controller->php_self
            : Tools::getValue('controller');
        if ($phpSelf !== 'contact') {
            return '';
        }
        $siteKey = Configuration::get('ZEYVRO_TURNSTILE_SITE_KEY');
        if (empty($siteKey)) {
            return '';
        }
        $mode = Configuration::get('ZEYVRO_TURNSTILE_MODE') ?: 'managed';

        $this->context->smarty->assign([
            'sbt_site_key' => $siteKey,
            'sbt_mode'     => $mode,
        ]);
        return $this->display(__FILE__, 'views/templates/front/turnstile_widget.tpl');
    }

    /**
     * Validación server-side del token Turnstile.
     * Se dispara en setMedia(), antes de initContent() donde contactform
     * llama a sendMessage(). Los errores añadidos aquí son vistos por sendMessage().
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return;
        }
        if (!isset($this->context->controller->php_self)
            || $this->context->controller->php_self !== 'contact') {
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Tools::isSubmit('submitMessage')) {
            return;
        }

        $secretKey = Configuration::get('ZEYVRO_TURNSTILE_SECRET_KEY');
        if (empty($secretKey)) {
            return;
        }

        $token    = (string) Tools::getValue('cf-turnstile-response', '');
        $remoteIp = Tools::getRemoteAddr();
        $result   = $this->verifyCloudflareTurnstile($secretKey, $token, $remoteIp);

        $this->logVerification($remoteIp, $result);

        if (!$result['success']
            && Configuration::get('ZEYVRO_TURNSTILE_ACTION_ON_FAIL') !== 'log_only') {
            $this->context->controller->errors[] = $this->l(
                'La verificación de seguridad no se completó. Por favor, inténtalo de nuevo.'
            );
        }
    }

    /* =====================================================================
     * PRIVADOS
     * =================================================================== */

    private function verifyCloudflareTurnstile(string $secret, string $token, string $remoteIp): array
    {
        $timeout  = max(1, (int) Configuration::get('ZEYVRO_TURNSTILE_API_TIMEOUT'));
        $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $postData = http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            PrestaShopLogger::addLog(
                '[zeyvro_turnstile] cURL error: ' . $curlError,
                3
            );
            // Fallo de red → bloquear por defecto (fail-safe)
            return ['success' => false, 'error_codes' => ['network-error'], 'score' => null];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['success' => false, 'error_codes' => ['invalid-response'], 'score' => null];
        }

        return [
            'success'     => !empty($data['success']),
            'error_codes' => $data['error-codes'] ?? [],
            'score'       => isset($data['score']) ? (float) $data['score'] : null,
        ];
    }

    private function logVerification(string $ip, array $result): void
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_LOG_ENABLED')) {
            return;
        }
        $ua         = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $errorCodes = !empty($result['error_codes']) ? implode(',', $result['error_codes']) : null;

        Db::getInstance()->insert('zeyvro_turnstile_log', [
            'ip'          => pSQL($ip),
            'user_agent'  => pSQL(Tools::substr($ua, 0, 255)),
            'date_add'    => date('Y-m-d H:i:s'),
            'success'     => (int) $result['success'],
            'score'       => $result['score'] !== null ? (float) $result['score'] : null,
            'error_codes' => $errorCodes !== null ? pSQL($errorCodes) : null,
        ]);
    }

    // ─── AUTO-UPGRADE §7.1 ───────────────────────────────────────────────────

    private function runAutoUpgrade(): void
    {
        try {
            $installed = (string) Configuration::get('ZEYVROTURNSTILE_VERSION');
            if (!$installed || !preg_match('/^\d+\.\d+\.\d+$/', $installed)) {
                $installed = (string) Db::getInstance()->getValue(
                    'SELECT `version` FROM `' . _DB_PREFIX_ . 'module`
                     WHERE `name` = "zeyvro_turnstile"'
                );
            }
            if (!$installed || !preg_match('/^\d+\.\d+\.\d+$/', $installed)) {
                return;
            }
            $xmlPath = dirname(__FILE__) . '/config.xml';
            if (!file_exists($xmlPath)) { return; }
            $xml = @simplexml_load_file($xmlPath);
            if (!$xml) { return; }
            $target = (string) $xml->version;
            if (!preg_match('/^\d+\.\d+\.\d+$/', $target)) { return; }
            if (version_compare($installed, $target, '>=')) { return; }
            define('ZEYVROTURNSTILE_UPGRADING', true);
            $scripts = glob(dirname(__FILE__) . '/upgrade/upgrade-*.php');
            if ($scripts) {
                usort($scripts, function ($a, $b) {
                    $va = preg_replace('/.*upgrade-(.+)\.php$/', '$1', $a);
                    $vb = preg_replace('/.*upgrade-(.+)\.php$/', '$1', $b);
                    return version_compare($va, $vb);
                });
                foreach ($scripts as $script) {
                    $sv = preg_replace('/.*upgrade-(.+)\.php$/', '$1', $script);
                    if (version_compare($sv, $installed, '>') && version_compare($sv, $target, '<=')) {
                        include_once $script;
                        $fn = 'upgrade_module_' . str_replace('.', '_', $sv);
                        if (function_exists($fn) && !$fn($this)) {
                            PrestaShopLogger::addLog(
                                'zeyvro_turnstile: upgrade script ' . $sv . ' failed',
                                3, null, 'zeyvro_turnstile', 0, true
                            );
                            return;
                        }
                    }
                }
            }
            Configuration::updateValue('ZEYVROTURNSTILE_VERSION', $target);
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'module` SET `version` = "' . pSQL($target) . '"
                 WHERE `name` = "zeyvro_turnstile"'
            );
            $this->installTab();
            $this->clearAllCaches();
        } catch (\Exception $e) {
            PrestaShopLogger::addLog(
                'zeyvro_turnstile auto-upgrade error: ' . $e->getMessage(),
                3, null, 'zeyvro_turnstile', 0, true
            );
        }
    }

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
        } catch (\Throwable $t) {
            // best-effort — never break install/upgrade
        }
    }
}
