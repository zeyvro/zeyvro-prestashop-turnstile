<?php
/**
 * AdminZeyvroTurnstileController
 * Settings panel and log for zeyvro_turnstile module.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminZeyvroTurnstileController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap  = true;
        $this->table      = 'zeyvro_turnstile_log';
        $this->identifier = 'id_log';
        parent::__construct();
        $this->meta_title = $this->l('Zeyvro Turnstile');
    }

    /* =====================================================================
     * POST PROCESSING (form save + clear logs)
     * =================================================================== */

    public function postProcess(): void
    {
        if (Tools::isSubmit('submitZeyvroTurnstile')) {
            $this->saveSettings();
        }

        if (Tools::getValue('clearOldLogs')) {
            $this->clearOldLogs();
        }

        parent::postProcess();
    }

    private function saveSettings(): void
    {
        $map = [
            'ZEYVRO_TURNSTILE_ENABLED'       => 'int',
            'ZEYVRO_TURNSTILE_SITE_KEY'       => 'string',
            'ZEYVRO_TURNSTILE_SECRET_KEY'     => 'string',
            'ZEYVRO_TURNSTILE_MODE'           => 'string',
            'ZEYVRO_TURNSTILE_ACTION_ON_FAIL' => 'string',
            'ZEYVRO_TURNSTILE_LOG_ENABLED'    => 'int',
            'ZEYVRO_TURNSTILE_API_TIMEOUT'    => 'int',
        ];

        $allowedModes       = ['managed', 'invisible', 'non-interactive'];
        $allowedFailActions = ['block', 'log_only'];

        $mode       = Tools::getValue('ZEYVRO_TURNSTILE_MODE', 'managed');
        $failAction = Tools::getValue('ZEYVRO_TURNSTILE_ACTION_ON_FAIL', 'block');

        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'managed';
        }
        if (!in_array($failAction, $allowedFailActions, true)) {
            $failAction = 'block';
        }

        $secretFields = ['ZEYVRO_TURNSTILE_SECRET_KEY'];

        foreach ($map as $key => $type) {
            $val = Tools::getValue($key);
            if ($key === 'ZEYVRO_TURNSTILE_MODE') {
                $val = $mode;
            } elseif ($key === 'ZEYVRO_TURNSTILE_ACTION_ON_FAIL') {
                $val = $failAction;
            } elseif ($type === 'int') {
                $val = (int) $val;
            } else {
                $val = trim((string) $val);
            }
            // No sobrescribir campos secretos si se envían vacíos
            if (in_array($key, $secretFields, true) && $val === '') {
                continue;
            }
            Configuration::updateValue($key, $val);
        }

        $this->confirmations[] = $this->l('Configuración guardada correctamente.');
    }

    private function clearOldLogs(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));
        $deleted = Db::getInstance()->delete(
            'zeyvro_turnstile_log',
            "date_add < '" . pSQL($cutoff) . "'"
        );
        $this->confirmations[] = $this->l('Logs de más de 30 días eliminados.');
    }

    /* =====================================================================
     * RENDER
     * =================================================================== */

    public function initContent(): void
    {
        parent::initContent();

        $tplDir = _PS_MODULE_DIR_ . 'zeyvro_turnstile/views/templates/admin/';
        $this->context->smarty->addTemplateDir($tplDir);

        $this->context->smarty->assign([
            'form_html'       => $this->renderSettingsForm(),
            'logs'            => $this->getRecentLogs(50),
            'log_total'       => $this->getLogCount(),
            'clear_url'       => $this->context->link->getAdminLink('AdminZeyvroTurnstile') . '&clearOldLogs=1',
            'confirmations'   => $this->confirmations,
            'errors'          => $this->errors,
        ]);

        $content = $this->context->smarty->fetch($tplDir . 'settings.tpl');
        $this->context->smarty->assign('content', $content);
    }

    private function renderSettingsForm(): string
    {
        $fields = [
            [
                'type'    => 'switch',
                'label'   => $this->l('Activar Turnstile'),
                'name'    => 'ZEYVRO_TURNSTILE_ENABLED',
                'is_bool' => true,
                'values'  => [
                    ['id' => 'ZEYVRO_TURNSTILE_ENABLED_on',  'value' => 1, 'label' => $this->l('Sí')],
                    ['id' => 'ZEYVRO_TURNSTILE_ENABLED_off', 'value' => 0, 'label' => $this->l('No')],
                ],
                'desc'    => $this->l('Desactívalo para deshabilitar temporalmente sin desinstalar.'),
            ],
            [
                'type'     => 'text',
                'label'    => $this->l('Site Key'),
                'name'     => 'ZEYVRO_TURNSTILE_SITE_KEY',
                'required' => true,
                'desc'     => $this->l('Clave pública del widget (dash.cloudflare.com → Turnstile → tu sitio).'),
            ],
            [
                'type'     => 'password',
                'label'    => $this->l('Secret Key'),
                'name'     => 'ZEYVRO_TURNSTILE_SECRET_KEY',
                'required' => true,
                'desc'     => $this->l('Clave secreta para verificar el token en el servidor. Nunca la expongas. Deja este campo en blanco al guardar para conservar el valor actual.'),
            ],
            [
                'type'    => 'select',
                'label'   => $this->l('Modo del widget'),
                'name'    => 'ZEYVRO_TURNSTILE_MODE',
                'options' => [
                    'query' => [
                        ['id' => 'managed',        'name' => $this->l('Managed (automático, recomendado)')],
                        ['id' => 'non-interactive', 'name' => $this->l('Non-interactive (sin clic)')],
                        ['id' => 'invisible',       'name' => $this->l('Invisible (sin widget visible)')],
                    ],
                    'id'   => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'type'    => 'select',
                'label'   => $this->l('Acción si falla la verificación'),
                'name'    => 'ZEYVRO_TURNSTILE_ACTION_ON_FAIL',
                'options' => [
                    'query' => [
                        ['id' => 'block',    'name' => $this->l('Bloquear envío (recomendado)')],
                        ['id' => 'log_only', 'name' => $this->l('Solo registrar (dejar pasar)')],
                    ],
                    'id'   => 'id',
                    'name' => 'name',
                ],
                'desc'    => $this->l('En "solo registrar" el formulario se envía aunque falle Turnstile.'),
            ],
            [
                'type'    => 'switch',
                'label'   => $this->l('Registrar intentos'),
                'name'    => 'ZEYVRO_TURNSTILE_LOG_ENABLED',
                'is_bool' => true,
                'values'  => [
                    ['id' => 'ZEYVRO_TURNSTILE_LOG_ENABLED_on',  'value' => 1, 'label' => $this->l('Sí')],
                    ['id' => 'ZEYVRO_TURNSTILE_LOG_ENABLED_off', 'value' => 0, 'label' => $this->l('No')],
                ],
            ],
            [
                'type'  => 'text',
                'label' => $this->l('Timeout API (segundos)'),
                'name'  => 'ZEYVRO_TURNSTILE_API_TIMEOUT',
                'class' => 'fixed-width-sm',
                'desc'  => $this->l('Tiempo máximo de espera para la llamada a Cloudflare. Por defecto: 5.'),
            ],
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de Turnstile'),
                    'icon'  => 'icon-shield',
                ],
                'input'  => $fields,
                'submit' => [
                    'name'  => 'submitZeyvroTurnstile',
                    'title' => $this->l('Guardar'),
                ],
            ],
        ];

        $fieldsValue = [];
        foreach (ZeyvroTurnstile::CONFIG_KEYS as $k) {
            $fieldsValue[$k] = Configuration::get($k);
        }

        $helper                        = new HelperForm();
        $helper->show_toolbar          = false;
        $helper->table                 = 'zeyvro_turnstile_log';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->submit_action         = 'submitZeyvroTurnstile';
        $helper->currentIndex          = $this->context->link->getAdminLink('AdminZeyvroTurnstile', false);
        $helper->token                 = Tools::getAdminTokenLite('AdminZeyvroTurnstile');
        $helper->tpl_vars              = [
            'fields_value' => $fieldsValue,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /* =====================================================================
     * DATA HELPERS
     * =================================================================== */

    private function getRecentLogs(int $limit): array
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "zeyvro_turnstile_log`
                ORDER BY `date_add` DESC";

        return Db::getInstance()->executeS($sql . " LIMIT " . (int) $limit) ?: [];
    }

    private function getLogCount(): int
    {
        return (int) Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "zeyvro_turnstile_log`"
        );
    }
}
