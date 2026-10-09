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

class AdminZeyvroTurnstileController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'zeyvro_turnstile_log';
        $this->identifier = 'id_log';
        parent::__construct();
        $this->meta_title = $this->l('Zeyvro Turnstile');
    }

    /**
     * PS8/PS9 compatibility: ModuleAdminControllerCore::l() exists in PS8 with
     * signature ($string, $class, $addslashes, $htmlentities); PS9 removed it.
     * Signature must match the PS8 parent to avoid Compile Error on PS8.
     * Delegates to Module::l() which handles i18n uniformly on both versions.
     *
     * @param string $string
     * @param string|null $class
     * @param bool $addslashes
     * @param bool $htmlentities
     *
     * @return string
     */
    public function l($string, $class = null, $addslashes = false, $htmlentities = true): string
    {
        return $this->module->l($string, basename(__FILE__, '.php'));
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
            'ZEYVRO_TURNSTILE_ENABLED' => 'int',
            'ZEYVRO_TURNSTILE_SITE_KEY' => 'string',
            'ZEYVRO_TURNSTILE_SECRET_KEY' => 'string',
            'ZEYVRO_TURNSTILE_MODE' => 'string',
            'ZEYVRO_TURNSTILE_ACTION_ON_FAIL' => 'string',
            'ZEYVRO_TURNSTILE_LOG_ENABLED' => 'int',
            'ZEYVRO_TURNSTILE_API_TIMEOUT' => 'int',
            'ZEYVRO_TURNSTILE_FORM_REGISTER' => 'int',
            'ZEYVRO_TURNSTILE_FORM_LOGIN' => 'int',
            'ZEYVRO_TURNSTILE_FORM_PASSWORD' => 'int',
            'ZEYVRO_TURNSTILE_FORM_PRESTABLOG' => 'int',
            'ZEYVRO_TURNSTILE_FORM_EMAILALERTS' => 'int',
        ];

        $allowedModes = ['managed', 'invisible', 'non-interactive'];
        $allowedFailActions = ['block', 'log_only'];

        $mode = Tools::getValue('ZEYVRO_TURNSTILE_MODE', 'managed');
        $failAction = Tools::getValue('ZEYVRO_TURNSTILE_ACTION_ON_FAIL', 'block');

        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'managed';
        }
        if (!in_array($failAction, $allowedFailActions, true)) {
            $failAction = 'block';
        }

        $secretFields = ['ZEYVRO_TURNSTILE_SECRET_KEY', 'ZEYVRO_TURNSTILE_SITE_KEY'];

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

        $this->confirmations[] = $this->l('Settings saved successfully.');
    }

    private function clearOldLogs(): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));
        $deleted = Db::getInstance()->delete(
            'zeyvro_turnstile_log',
            "date_add < '" . pSQL($cutoff) . "'"
        );
        $this->confirmations[] = $this->l('Logs older than 30 days have been deleted.');
    }

    /* =====================================================================
     * RENDER
     * =================================================================== */

    public function initContent(): void
    {
        parent::initContent();

        $tplDir = _PS_MODULE_DIR_ . 'zeyvro_turnstile/views/templates/admin/';
        $this->context->smarty->addTemplateDir($tplDir);

        // Log panel — all variables escaped inside the template; no nofilter needed.
        $this->context->smarty->assign([
            'logs' => $this->getRecentLogs(50),
            'log_total' => $this->getLogCount(),
            'clear_url' => $this->context->link->getAdminLink('AdminZeyvroTurnstile') . '&clearOldLogs=1',
            'confirmations' => $this->confirmations,
            'errors' => $this->errors,
        ]);

        // Set $this->content directly — avoids {var nofilter} in templates (Validator check 14).
        // renderSettingsForm() returns HelperForm HTML (safe, PS-generated).
        $this->content = $this->renderSettingsForm()
            . $this->context->smarty->fetch($tplDir . 'settings.tpl');

        // parent::initContent() already assigned 'content' to Smarty with the empty string.
        // Re-assign now that $this->content is fully built.
        $this->context->smarty->assign('content', $this->content);
    }

    /**
     * v1.2.0 — interruptor Sí/No de un formulario protegido.
     */
    private function formSwitch(string $key, string $label, string $desc): array
    {
        $field = [
            'type' => 'switch',
            'label' => $label,
            'name' => $key,
            'is_bool' => true,
            'values' => [
                ['id' => $key . '_on',  'value' => 1, 'label' => $this->l('Yes')],
                ['id' => $key . '_off', 'value' => 0, 'label' => $this->l('No')],
            ],
        ];
        if ($desc !== '') {
            $field['desc'] = $desc;
        }

        return $field;
    }

    private function renderSettingsForm(): string
    {
        $fields = [
            [
                'type' => 'switch',
                'label' => $this->l('Enable Turnstile'),
                'name' => 'ZEYVRO_TURNSTILE_ENABLED',
                'is_bool' => true,
                'values' => [
                    ['id' => 'ZEYVRO_TURNSTILE_ENABLED_on',  'value' => 1, 'label' => $this->l('Yes')],
                    ['id' => 'ZEYVRO_TURNSTILE_ENABLED_off', 'value' => 0, 'label' => $this->l('No')],
                ],
                'desc' => $this->l('Disable it to temporarily deactivate without uninstalling.'),
            ],
            $this->formSwitch(
                'ZEYVRO_TURNSTILE_FORM_REGISTER',
                $this->l('Protect registration form'),
                $this->l('Account creation inside the checkout is not covered.')
            ),
            $this->formSwitch(
                'ZEYVRO_TURNSTILE_FORM_LOGIN',
                $this->l('Protect login form'),
                $this->l('Also covers the login window of themes that send it to the native login page.')
            ),
            $this->formSwitch(
                'ZEYVRO_TURNSTILE_FORM_PASSWORD',
                $this->l('Protect password recovery form'),
                ''
            ),
            $this->formSwitch(
                'ZEYVRO_TURNSTILE_FORM_PRESTABLOG',
                $this->l('Protect blog comments (prestablog)'),
                $this->l('Only applies if the module is installed and enabled.')
            ),
            $this->formSwitch(
                'ZEYVRO_TURNSTILE_FORM_EMAILALERTS',
                $this->l('Protect back-in-stock alerts (ps_emailalerts)'),
                $this->l('Only applies if the module is installed and enabled.')
            ),
            [
                'type' => 'password',
                'label' => $this->l('Site Key'),
                'name' => 'ZEYVRO_TURNSTILE_SITE_KEY',
                'required' => true,
                'desc' => $this->l('Public widget key. Leave blank when saving to keep the current value.'),
            ],
            [
                'type' => 'password',
                'label' => $this->l('Secret Key'),
                'name' => 'ZEYVRO_TURNSTILE_SECRET_KEY',
                'required' => true,
                'desc' => $this->l('Secret key to verify the token on the server. Never expose it. Leave this field blank when saving to keep the current value.'),
            ],
            [
                'type' => 'select',
                'label' => $this->l('Widget mode'),
                'name' => 'ZEYVRO_TURNSTILE_MODE',
                'options' => [
                    'query' => [
                        ['id' => 'managed',        'name' => $this->l('Managed (automatic, recommended)')],
                        ['id' => 'non-interactive', 'name' => $this->l('Non-interactive (no click)')],
                        ['id' => 'invisible',       'name' => $this->l('Invisible (no visible widget)')],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
            [
                'type' => 'select',
                'label' => $this->l('Action on verification failure'),
                'name' => 'ZEYVRO_TURNSTILE_ACTION_ON_FAIL',
                'options' => [
                    'query' => [
                        ['id' => 'block',    'name' => $this->l('Block submission (recommended)')],
                        ['id' => 'log_only', 'name' => $this->l('Log only (allow through)')],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
                'desc' => $this->l('In "log only" mode the form is submitted even if Turnstile fails.'),
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Log attempts'),
                'name' => 'ZEYVRO_TURNSTILE_LOG_ENABLED',
                'is_bool' => true,
                'values' => [
                    ['id' => 'ZEYVRO_TURNSTILE_LOG_ENABLED_on',  'value' => 1, 'label' => $this->l('Yes')],
                    ['id' => 'ZEYVRO_TURNSTILE_LOG_ENABLED_off', 'value' => 0, 'label' => $this->l('No')],
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('API timeout (seconds)'),
                'name' => 'ZEYVRO_TURNSTILE_API_TIMEOUT',
                'class' => 'fixed-width-sm',
                'desc' => $this->l('Maximum wait time for the Cloudflare API call. Default: 5.'),
            ],
        ];

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Turnstile Configuration'),
                    'icon' => 'icon-shield',
                ],
                'input' => $fields,
                'submit' => [
                    'name' => 'submitZeyvroTurnstile',
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $fieldsValue = [];
        // v1.2.0 — de los campos que pinta este formulario, no de Zeyvro_Turnstile::CONFIG_KEYS: justo tras subir el
        // ZIP el servidor puede seguir sirviendo la clase vieja y sin las claves nuevas el formulario da error 500.
        foreach ($fields as $field) {
            $fieldsValue[$field['name']] = Configuration::get($field['name']);
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = 'zeyvro_turnstile_log';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->submit_action = 'submitZeyvroTurnstile';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminZeyvroTurnstile', false);
        $helper->token = Tools::getAdminTokenLite('AdminZeyvroTurnstile');
        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$form]);
    }

    /* =====================================================================
     * DATA HELPERS
     * =================================================================== */

    private function getRecentLogs(int $limit): array
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'zeyvro_turnstile_log`
                ORDER BY `date_add` DESC';

        return Db::getInstance()->executeS($sql . ' LIMIT ' . (int) $limit) ?: [];
    }

    private function getLogCount(): int
    {
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'zeyvro_turnstile_log`'
        );
    }
}
