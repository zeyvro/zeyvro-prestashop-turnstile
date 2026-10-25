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

require_once __DIR__ . '/classes/ZeyvroModuleTrait.php';

class Zeyvro_Turnstile extends Module
{
    use ZeyvroModuleTrait;

    // ── Base común: constantes de identidad ───────────────────────────────
    public const ZV_TAB_CLASS = 'AdminZeyvroTurnstile';
    public const ZV_TAB_NAME = 'Anti SPAM';
    public const ZV_TAB_ICON = 'verified_user';
    public const ZV_ADS_VARIANT = 'free';
    public const ZV_LICENSE_TYPE = 'free';
    public const ZV_SCHEMA_TABV = 'A';               // cambiar solo si la estructura de tabs cambia
    public const ZV_SCHEMA_KEY = 'ZEYVROTURNSTILE_TABV';
    public const ZV_HOOKS = ['actionDispatcher', 'actionSubmitAccountBefore'];   // v1.2.0 — turnstileEnsureHooks

    // ── Config keys propios del módulo ────────────────────────────────────
    public const CONFIG_KEYS = [
        'ZEYVRO_TURNSTILE_ENABLED',
        'ZEYVRO_TURNSTILE_SITE_KEY',
        'ZEYVRO_TURNSTILE_SECRET_KEY',
        'ZEYVRO_TURNSTILE_MODE',
        'ZEYVRO_TURNSTILE_ACTION_ON_FAIL',
        'ZEYVRO_TURNSTILE_LOG_ENABLED',
        'ZEYVRO_TURNSTILE_API_TIMEOUT',
        'ZEYVRO_TURNSTILE_FORM_REGISTER',
        'ZEYVRO_TURNSTILE_FORM_LOGIN',
        'ZEYVRO_TURNSTILE_FORM_PASSWORD',
        'ZEYVRO_TURNSTILE_FORM_PRESTABLOG',
        'ZEYVRO_TURNSTILE_FORM_EMAILALERTS',
    ];

    // v1.2.0 — un interruptor por formulario. ZEYVRO_TURNSTILE_ENABLED sigue siendo el general y el contacto
    // no tiene interruptor propio: se comporta como en la 1.1.10.
    public const FORM_KEYS = [
        'register' => 'ZEYVRO_TURNSTILE_FORM_REGISTER',
        'login' => 'ZEYVRO_TURNSTILE_FORM_LOGIN',
        'password' => 'ZEYVRO_TURNSTILE_FORM_PASSWORD',
        'prestablog' => 'ZEYVRO_TURNSTILE_FORM_PRESTABLOG',
        'emailalerts' => 'ZEYVRO_TURNSTILE_FORM_EMAILALERTS',
    ];

    /** @var array<string, bool> formularios cuya verificación ha fallado en esta petición */
    private static $sbtFailed = [];

    /** v1.2.1 — formularios ya verificados en actionDispatcher en esta petición (un token vale una vez). */
    private static $dispatcherChecked = [];

    public function __construct()
    {
        $this->name = 'zeyvro_turnstile';
        $this->tab = 'front_office_features';
        $this->version = '1.2.1';
        $this->author = 'Zeyvro';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '9.2.0'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Zeyvro Turnstile');
        $this->description = $this->l(
            'Anti-spam protection with Cloudflare Turnstile on the contact, registration, login and password recovery forms, blog comments and back-in-stock alerts.'
        );

        // §7.1 — Auto-upgrade al subir ZIP por BO
        if (defined('_PS_ADMIN_DIR_') && !defined('ZEYVROTURNSTILE_UPGRADING')) {
            $this->runAutoUpgrade();
        }
        // v1.2.0 — los hooks nuevos (ZV_HOOKS) se registran aquí, ya con la clase nueva cargada, y no en
        // upgrade-1.2.0.php: en PS 9 registerHook() mira el método en la clase vieja y la actualización falla.
        // En BO y en front: una tienda actualizada queda protegida en la primera petición que cargue la clase nueva,
        // aunque nadie abra el BO. Con la firma ya guardada cuesta un Configuration::get (en memoria).
        if ($this->id && !defined('ZEYVROTURNSTILE_UPGRADING')) {
            $this->turnstileEnsureHooks();
        }
    }

    /**
     * Registra los hooks de ZV_HOOKS que falten. Misma lógica que zvEnsureHooks() del trait, pero en la clase:
     * ZeyvroModuleTrait es global y cada módulo Zeyvro trae su copia con trait_exists(), así que gana la primera
     * que se carga. Con otro módulo Zeyvro de trait anterior cargado antes, zvEnsureHooks() no existe y llamarlo
     * desde el constructor tumbaba todo el BO con un 500 (convivencia ps-8-2, 2026-10-09). Idempotente y guarded
     * por firma (versión + lista) en ZV_SCHEMA_KEY . '_HOOKS', la misma clave que el trait. Nunca rompe la petición.
     *
     * @return bool true si todos los hooks declarados quedan registrados
     */
    private function turnstileEnsureHooks(): bool
    {
        try {
            $key = self::ZV_SCHEMA_KEY . '_HOOKS';
            $firma = md5($this->version . '|' . implode(',', self::ZV_HOOKS));
            if (Configuration::get($key) === $firma) {
                return true;
            }
            $ok = true;
            foreach (self::ZV_HOOKS as $hook) {
                if (!$this->registerHook($hook)) {
                    $ok = false;
                    $msg = 'turnstileEnsureHooks: no se pudo registrar el hook ' . $hook;
                    PrestaShopLogger::addLog($msg, 3, null, 'ZeyvroTurnstile', 0, true);
                }
            }
            if ($ok) {
                Configuration::updateValue($key, $firma);
            }

            return $ok;
        } catch (Throwable $t) {
            return false;
        }
    }

    /* =====================================================================
     * INSTALL / UNINSTALL
     * =================================================================== */

    public function install(): bool
    {
        return parent::install()
            && $this->installBase()             // trait: hook auto-reparación + ensureTabs + clearAllCaches
            && $this->installSql()
            && $this->installConfig()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionDispatcher')
            && $this->registerHook('actionSubmitAccountBefore');
    }

    public function uninstall(): bool
    {
        // PRESERVATIVO: tablas, configuración y datos sobreviven.
        // Para limpieza completa: ver sql/uninstall.sql (acción manual documentada).
        $this->uninstallBase();                 // trait: zvUninstallTab + unregister auto-reparación
        $this->unregisterHook('displayHeader');
        $this->unregisterHook('displayBeforeBodyClosingTag');
        $this->unregisterHook('actionFrontControllerSetMedia');
        $this->unregisterHook('actionDispatcher');
        $this->unregisterHook('actionSubmitAccountBefore');

        return parent::uninstall();
    }

    private function installSql(): bool
    {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . "zeyvro_turnstile_log` (
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

    private function installConfig(): bool
    {
        $defaults = [
            'ZEYVRO_TURNSTILE_ENABLED' => 0,
            'ZEYVRO_TURNSTILE_SITE_KEY' => '',
            'ZEYVRO_TURNSTILE_SECRET_KEY' => '',
            'ZEYVRO_TURNSTILE_MODE' => 'managed',
            'ZEYVRO_TURNSTILE_ACTION_ON_FAIL' => 'block',
            'ZEYVRO_TURNSTILE_LOG_ENABLED' => 1,
            'ZEYVRO_TURNSTILE_API_TIMEOUT' => 5,
            // v1.2.0 — encendidos en instalación limpia; upgrade-1.2.0.php los crea apagados
            'ZEYVRO_TURNSTILE_FORM_REGISTER' => 1,
            'ZEYVRO_TURNSTILE_FORM_LOGIN' => 1,
            'ZEYVRO_TURNSTILE_FORM_PASSWORD' => 1,
            'ZEYVRO_TURNSTILE_FORM_PRESTABLOG' => 1,
            'ZEYVRO_TURNSTILE_FORM_EMAILALERTS' => 1,
        ];
        foreach ($defaults as $k => $v) {
            Configuration::updateValue($k, $v);
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
     * HOOKS — Cloudflare Turnstile
     * =================================================================== */

    /**
     * Inyecta el script de la API de Turnstile solo en la página de contacto.
     */
    public function hookDisplayHeader($params): string
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return '';
        }
        $phpSelf = isset($this->context->controller->php_self)
            ? $this->context->controller->php_self
            : null;
        if ($phpSelf !== 'contact') {
            return '';
        }
        if (empty(Configuration::get('ZEYVRO_TURNSTILE_SITE_KEY'))) {
            return '';
        }

        $this->context->controller->registerJavascript(
            'turnstile-api',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            ['server' => 'remote', 'position' => 'head', 'priority' => 150, 'attributes' => 'async defer']
        );

        return '';
    }

    /**
     * Inyecta el widget Turnstile vía JS en los formularios protegidos de la página (contacto como en la 1.1.10).
     */
    public function hookDisplayBeforeBodyClosingTag($params): string
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return '';
        }
        $phpSelf = isset($this->context->controller->php_self)
            ? $this->context->controller->php_self
            : null;
        $forms = [
            'contact' => $phpSelf === 'contact',
            // v1.2.1 — también el checkout: alta con contraseña y pedido como invitado (los dos envían submitCreate)
            'register' => in_array($phpSelf, ['registration', 'order'], true) && $this->isFormOn('register'),
            // En todas las páginas: la ventana de login de los temas envía a la página de login nativa
            'login' => !$this->context->customer->isLogged() && $this->isFormOn('login'),
            'password' => $phpSelf === 'password' && $this->isFormOn('password'),
            'prestablog' => $this->currentModuleName() === 'prestablog' && $this->isFormOn('prestablog'),
            'emailalerts' => $phpSelf === 'product' && $this->isFormOn('emailalerts'),
        ];
        if (!in_array(true, $forms, true)) {
            return '';
        }
        $siteKey = Configuration::get('ZEYVRO_TURNSTILE_SITE_KEY');
        if (empty($siteKey)) {
            return '';
        }
        $mode = Configuration::get('ZEYVRO_TURNSTILE_MODE') ?: 'managed';
        $failed = implode(',', array_diff(array_keys(self::$sbtFailed), ['prestablog']));

        $this->context->smarty->assign([
            'sbt_site_key' => $siteKey,
            'sbt_mode' => $mode,
            'sbt_forms' => $forms,
            'sbt_page' => (string) $phpSelf,
            'sbt_auth_url' => $this->context->link->getPageLink('authentication', true),
            // prestablog no: su mensaje va en el bloque de notificaciones de la página (controller->errors)
            'sbt_failed' => $failed,
            // Solo con algún formulario rechazado en esta petición: sin fallo, el HTML queda como en la 1.1.10
            'sbt_error' => $failed !== '' ? $this->l('Security verification failed. Please try again.') : '',
        ]);

        return $this->display(__FILE__, 'views/templates/front/turnstile_widget.tpl');
    }

    /**
     * Validación server-side del token Turnstile en el formulario de contacto.
     */
    public function hookActionFrontControllerSetMedia($params): void
    {
        // v1.2.0 — mensajes de los envíos rechazados en actionDispatcher (aquí el idioma ya está resuelto)
        if (!empty(self::$sbtFailed['prestablog'])) {
            $this->context->controller->errors[] = $this->l('Security verification failed. Please try again.');
        }
        if (!empty(self::$sbtFailed['emailalerts'])) {
            // Mismo contrato que ps_emailalerts/controllers/front/actions.php: su JS hace JSON.parse de la respuesta
            exit(json_encode([
                'error' => true,
                'message' => $this->l('Security verification failed. Please try again.'),
            ]));
        }
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return;
        }
        if (!isset($this->context->controller->php_self)
            || $this->context->controller->php_self !== 'contact') {
            return;
        }
        // v1.2.1 — POST o GET: contactform procesa submitMessage venga por donde venga
        if (!Tools::isSubmit('submitMessage')) {
            return;
        }

        $secretKey = Configuration::get('ZEYVRO_TURNSTILE_SECRET_KEY');
        if (empty($secretKey)) {
            return;
        }

        $token = (string) Tools::getValue('cf-turnstile-response', '');
        $remoteIp = Tools::getRemoteAddr();
        $result = $this->verifyCloudflareTurnstile($secretKey, $token, $remoteIp);

        $this->logVerification($remoteIp, $result);

        if (!$result['success']
            && Configuration::get('ZEYVRO_TURNSTILE_ACTION_ON_FAIL') !== 'log_only') {
            $this->context->controller->errors[] = $this->l(
                'Security verification failed. Please try again.'
            );
            // v1.1.8 — contactform < 4.4.3 (PS 8.0 / 8.1) guarda el mensaje en Servicio al cliente sin mirar
            // controller->errors. Sin submitMessage no entra en sendMessage(); el email y el mensaje se conservan.
            foreach (['submitMessage', 'submitMessage_x', 'submitMessage_y'] as $key) {
                unset($_POST[$key], $_GET[$key]);
            }
        }
    }

    /* =====================================================================
     * v1.2.0 — REGISTRO, LOGIN, CONTRASEÑA, PRESTABLOG Y PS_EMAILALERTS
     * =================================================================== */

    /**
     * true = el formulario está protegido: interruptor general + el suyo y, para prestablog y ps_emailalerts,
     * el módulo instalado y activo.
     */
    private function isFormOn(string $form): bool
    {
        if (!isset(self::FORM_KEYS[$form])
            || !(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')
            || !(int) Configuration::get(self::FORM_KEYS[$form])) {
            return false;
        }
        $modules = ['prestablog' => 'prestablog', 'emailalerts' => 'ps_emailalerts'];
        if (!isset($modules[$form])) {
            return true;
        }

        return Module::isInstalled($modules[$form]) && Module::isEnabled($modules[$form]);
    }

    /**
     * Nombre del módulo dueño del controlador de front actual ('' si no es un controlador de módulo).
     */
    private function currentModuleName(): string
    {
        $controller = $this->context->controller;
        if ($controller instanceof ModuleFrontController && $controller->module instanceof Module) {
            return (string) $controller->module->name;
        }

        return '';
    }

    /**
     * Verifica el token con el mismo log y el mismo ACTION_ON_FAIL que el contacto.
     * true = se deja pasar (token válido, sin clave secreta o modo «solo registrar»); false = hay que rechazarlo.
     */
    private function verifyFormToken(string $form): bool
    {
        $secretKey = Configuration::get('ZEYVRO_TURNSTILE_SECRET_KEY');
        if (empty($secretKey)) {
            return true;
        }
        $token = (string) Tools::getValue('cf-turnstile-response', '');
        $remoteIp = Tools::getRemoteAddr();
        $result = $this->verifyCloudflareTurnstile($secretKey, $token, $remoteIp);

        $this->logVerification($remoteIp, $result);

        if ($result['success'] || Configuration::get('ZEYVRO_TURNSTILE_ACTION_ON_FAIL') === 'log_only') {
            return true;
        }
        self::$sbtFailed[$form] = true;

        return false;
    }

    /**
     * v1.2.0 — Registro. El núcleo ejecuta este hook en RegistrationController::initContent() justo antes de
     * $register_form->submit() y devolver false impide crear la cuenta (corre también con ajax=1).
     * Desde 8.2 el checkout también lo ejecuta (CheckoutPersonalInformationStep): ahí no se verifica otra vez,
     * el registro y el pedido como invitado del checkout se verifican en actionDispatcher (v1.2.1).
     */
    public function hookActionSubmitAccountBefore($params): bool
    {
        if (isset(self::$dispatcherChecked['register'])) {
            return true;
        }
        if (!isset($this->context->controller->php_self)
            || $this->context->controller->php_self !== 'registration'
            || !$this->isFormOn('register')) {
            return true;
        }

        return $this->verifyFormToken('register');
    }

    /**
     * v1.2.0 — Verificación en el servidor del login, recuperar contraseña, comentarios de prestablog y avisos
     * de ps_emailalerts. Va en actionDispatcher (justo antes de Controller::run()) y no en
     * actionFrontControllerSetMedia: con ajax=1 o Accept: application/json, FrontController::init() pone
     * display_header=false, el núcleo no llama a setMedia() y el envío pasaría sin verificar.
     * Si falla, se quita del envío la clave que mira el controlador; el mensaje se pinta después, con el idioma
     * ya resuelto (plantilla del widget, o actionFrontControllerSetMedia para prestablog y ps_emailalerts).
     */
    public function hookActionDispatcher($params): void
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_ENABLED')) {
            return;
        }
        $type = isset($params['controller_type']) ? (int) $params['controller_type'] : 0;
        if ($type !== Dispatcher::FC_FRONT && $type !== Dispatcher::FC_MODULE) {
            return;
        }
        $phpSelf = isset($this->context->controller->php_self) ? (string) $this->context->controller->php_self : '';
        $form = null;
        $keys = [];
        if ($phpSelf === 'authentication' && Tools::isSubmit('submitLogin')) {
            $form = 'login';
            $keys = ['submitLogin', 'submitLogin_x', 'submitLogin_y'];
        } elseif ($phpSelf === 'order' && Tools::isSubmit('submitLogin')) {
            // v1.2.1 — login dentro del checkout (CheckoutPersonalInformationStep)
            $form = 'login';
            $keys = ['submitLogin', 'submitLogin_x', 'submitLogin_y'];
        } elseif ($phpSelf === 'order' && Tools::isSubmit('submitCreate')) {
            // v1.2.1 — alta y pedido como invitado del checkout (sin contraseña también envía submitCreate)
            $form = 'register';
            $keys = ['submitCreate'];
        } elseif ($phpSelf === 'password' && Tools::isSubmit('email')) {
            // Solo la petición del enlace; el formulario de la nueva contraseña (token + passwd) no se toca
            $form = 'password';
            $keys = ['email'];
        } elseif ($this->currentModuleName() === 'prestablog' && Tools::isSubmit('submitComment')) {
            $form = 'prestablog';
            $keys = ['submitComment', 'submitComment_x', 'submitComment_y'];
        } elseif ($this->currentModuleName() === 'ps_emailalerts' && Tools::getValue('process') === 'add') {
            $form = 'emailalerts';
            $keys = ['process'];
        }
        if ($form === null || !$this->isFormOn($form)) {
            return;
        }
        self::$dispatcherChecked[$form] = true;
        if ($this->verifyFormToken($form)) {
            return;
        }
        foreach ($keys as $key) {
            unset($_POST[$key], $_GET[$key]);
        }
        if ($phpSelf === 'order') {
            // El checkout redirige tras el POST (redirectWithNotifications): el aviso viaja en las notificaciones
            $this->context->controller->errors[] = $this->l('Security verification failed. Please try again.');
        }
    }

    /* =====================================================================
     * PRIVADOS — Cloudflare
     * =================================================================== */

    private function verifyCloudflareTurnstile(string $secret, string $token, string $remoteIp): array
    {
        $timeout = max(1, (int) Configuration::get('ZEYVRO_TURNSTILE_API_TIMEOUT'));
        $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $postData = http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            PrestaShopLogger::addLog('[zeyvro_turnstile] cURL error: ' . $curlError, 3);

            return ['success' => false, 'error_codes' => ['network-error'], 'score' => null];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['success' => false, 'error_codes' => ['invalid-response'], 'score' => null];
        }

        return [
            'success' => !empty($data['success']),
            'error_codes' => $data['error-codes'] ?? [],
            'score' => isset($data['score']) ? (float) $data['score'] : null,
        ];
    }

    private function logVerification(string $ip, array $result): void
    {
        if (!(int) Configuration::get('ZEYVRO_TURNSTILE_LOG_ENABLED')) {
            return;
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $errorCodes = !empty($result['error_codes']) ? implode(',', $result['error_codes']) : null;

        Db::getInstance()->insert('zeyvro_turnstile_log', [
            'ip' => pSQL($ip),
            'user_agent' => pSQL(Tools::substr($ua, 0, 255)),
            'date_add' => date('Y-m-d H:i:s'),
            'success' => (int) $result['success'],
            'score' => $result['score'] !== null ? (float) $result['score'] : null,
            'error_codes' => $errorCodes !== null ? pSQL($errorCodes) : null,
        ]);
    }

    /* =====================================================================
     * AUTO-UPGRADE §7.1
     * =================================================================== */

    private function runAutoUpgrade(): void
    {
        // v1.1.7 — En la gestión de módulos del BO (subir el ZIP, «Actualizar»…) el core ya ejecuta él mismo los
        // upgrade/*.php. Adelantarlos aquí hace que PS 9.1 los vea duplicados (Module::runUpgradeModule) y responda
        // status=false aunque la actualización se haya hecho. En el resto de peticiones del BO sigue igual: PS 9.0.0
        // no aplica los upgrade/*.php al subir el ZIP y es esto lo que lo cubre.
        if (self::isModuleManagerRequest()) {
            return;
        }

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
            $xmlPath = __DIR__ . '/config.xml';
            if (!file_exists($xmlPath)) {
                return;
            }
            $xml = @simplexml_load_file($xmlPath);
            if (!$xml) {
                return;
            }
            $target = (string) $xml->version;
            if (!preg_match('/^\d+\.\d+\.\d+$/', $target)) {
                return;
            }
            if (version_compare($installed, $target, '>=')) {
                return;
            }
            define('ZEYVROTURNSTILE_UPGRADING', true);
            $scripts = glob(__DIR__ . '/upgrade/upgrade-*.php');
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
                                3, null, 'zeyvroturnstile', 0, true
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
            $this->ensureTabs();
            $this->clearAllCaches();
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'zeyvro_turnstile auto-upgrade error: ' . $e->getMessage(),
                3, null, 'zeyvroturnstile', 0, true
            );
        }
    }

    /**
     * true = la petición actual es la gestión de módulos del BO (rutas Symfony /improve/modules/…:
     * admin_module_import, admin_module_manage_action, admin_module_manage_update_all…), donde
     * ModuleManager ya ejecuta los upgrade/*.php.
     */
    private static function isModuleManagerRequest(): bool
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';

        return strpos($uri, '/improve/modules/') !== false;
    }
}
