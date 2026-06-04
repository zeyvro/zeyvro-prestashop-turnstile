<?php
/**
 * Stubs PS para PHPStan - sin guards (PHPStan parsea estaticamente).
 */

function pSQL(string $string, bool $htmlOK = false): string { return $string; }

abstract class ObjectModel {
    public int $id = 0;
    public static function isLoadedObject(object $object, ?int $id_lang = null): bool { return false; }
}

abstract class Module extends ObjectModel {
    public string $name = '';
    public string $tab = '';
    public string $version = '';
    public string $author = '';
    public int $need_instance = 0;
    public bool $bootstrap = false;
    public array $ps_versions_compliancy = [];
    public string $displayName = '';
    public string $description = '';
    public string $confirmUninstall = '';
    public \Context $context;
    public function __construct() {}
    public function install(): bool { return false; }
    public function uninstall(): bool { return false; }
    public function l(string $s, string $specific = '', ?string $locale = null): string { return $s; }
    public function registerHook(string $hook): bool { return false; }
    public function getPathUri(): string { return ''; }
    public static function getInstanceByName(string $name): ?static { return null; }
    public function display(string $file, string $template, ?string $cache_id = null, ?string $compile_id = null): string { return ''; }
    public string $_path = '';
    public function unregisterHook(string $hook): bool { return false; }
    public static function isEnabled(string $moduleName, ?int $id_shop = null): bool { return false; }
}

abstract class Controller {
    public \Context $context;
    public string $content = '';
    public function addCSS(string $css_uri, string $media = 'all'): void {}
    public function setTemplate(string $template): void {}
    public function initContent(): void {}
    public function postProcess(): void {}
}

abstract class FrontController extends Controller {
    public string $php_self = '';
    public string $page_name = '';
}

abstract class ModuleFrontController extends FrontController {
    public Module $module;
    public function init(): void {}
    public function setMedia(): void {}
}

abstract class AdminController extends Controller {
    public Module $module;
    public bool $bootstrap = false;
    public string $meta_title = '';
    public array $confirmations = [];
    public array $errors = [];
    public string $table = '';
    public string $identifier = '';
    public string $className = '';
    public function __construct() {}
    public function l(string $s, string $specific = '', ?string $locale = null): string { return $s; }
    public string $lang = '';
    public array $fields_list = [];
    public array $bulk_actions = [];
    public array $page_header_toolbar_btn = [];
    public string $token = '';
    public static string $currentIndex = '';
    public string $_select = '';
    public string $_where = '';
    public string $_orderBy = '';
    public string $_orderWay = '';
    public function addRowAction(string $action): void {}
    public function initPageHeaderToolbar(): void {}
    public static function setMedia(bool $isNewTheme = false): void {}
    public function renderList(): string { return ''; }
    public function addJS(mixed $js_uri, ?int $id_shop = null): void {}
    public function addCSS(string $css_uri, string $media = 'all'): void {}
}

abstract class ModuleAdminController extends AdminController {}

abstract class OrderControllerCore extends FrontController {}
abstract class CartControllerCore extends FrontController {}

class Configuration {
    public static function get(string $key, ?int $id_lang = null, ?int $id_shop_group = null, ?int $id_shop = null, mixed $default = false): mixed { return $default; }
    public static function updateValue(string $key, mixed $values, bool $html = false, ?int $id_shop_group = null, ?int $id_shop = null): bool { return true; }
    public static function deleteByName(string $key): bool { return true; }
}

class Tools {
    public static function getValue(string $key, mixed $default_value = false): mixed { return $default_value; }
    public static function isSubmit(string $key): bool { return false; }
    public static function redirect(string $url, string $baseUri = __PS_BASE_URI__, ?object $controller = null, ?array $headers = null): void { exit; }
    public static function redirectAdmin(string $url): void { exit; }
    public static function getToken(bool $page = true): string { return ''; }
    public static function displayPrice(float $price, mixed $currency = null): string { return ''; }
    public static function isEmail(string $email): bool { return false; }
    public static function encrypt(string $passwd): string { return ''; }
    public static function passwdGen(int $length = 8, string $flag = 'ALPHANUMERIC'): string { return ''; }
    public static function getAdminTokenLite(string $tab, ?int $id_shop = null): string { return ''; }
    public static function getRemoteAddr(): string { return ''; }
    public static function substr(string $str, int $start, ?int $length = null): string { return ''; }
    public static function sanitize(string $string, bool $htmlOK = false): string { return ''; }
    public static function displayDate(string $date, ?int $id_lang = null, bool $full = false, string $separator = ' '): string { return ''; }
    public static function strtolower(string $str): string { return strtolower($str); }
    public static function strtoupper(string $str): string { return strtoupper($str); }
    public static function nl2br(string $str): string { return ''; }
    public static function getHttpHost(bool $http = false, bool $entities = true): string { return ''; }
    public static function rtrimString(string $str): string { return ''; }
    public static function strlen(string $str): int { return strlen($str); }
    public static function strpos(string $haystack, string $needle, int $offset = 0): int|false { return strpos($haystack, $needle, $offset); }
    public static function htmlentitiesUTF8(string $str): string { return ''; }
    public static function htmlentitiesDecodeUTF8(string $str): string { return ''; }
}

class Validate {
    public static function isEmail(string $email): bool { return false; }
    public static function isName(string $name): bool { return false; }
    public static function isMessage(string $message): bool { return false; }
    public static function isLoadedObject(object $object, ?int $id_lang = null): bool { return false; }
}

class Customer extends ObjectModel {
    public string $email = '';
    public string $firstname = '';
    public string $lastname = '';
    public string $passwd = '';
    public function __construct(int $id = 0) {}
    public static function customerExists(string $email, bool $returnId = false, bool $ignoreGuest = true): bool { return false; }
    public static function customerIdExistsStatic(int $id_customer): bool { return false; }
}

class Address extends ObjectModel {
    public string $firstname = '';
    public string $lastname = '';
    public function __construct(int $id = 0) {}
}

class Cart extends ObjectModel {
    public const ONLY_PRODUCTS = 1;
    public const ONLY_SHIPPING = 2;
    public const ONLY_DISCOUNTS = 3;
    public const BOTH = 4;
    public function getOrderTotal(bool $withTaxes = true, int $type = 4): float { return 0.0; }
    public function getProducts(bool $refresh = false): array { return []; }
}

class CartRule extends ObjectModel {
    public function __construct(int $id = 0) {}
    public static function getIdByCode(string $code): int { return 0; }
}

class State extends ObjectModel {
    public static function getStatesByIdCountry(int $id_country, bool $active = false): array { return []; }
}

class Country extends ObjectModel {
    public static function getCountries(int $id_lang, bool $active = false, bool $contain_states = false, bool $list_iso_code = false): array { return []; }
}

class Gender extends ObjectModel {
    public static function getGenders(int $id_lang): array { return []; }
}

class StockAvailable {
    public static function getQuantityAvailableByProduct(?int $id_product = null, ?int $id_product_attribute = null, ?int $id_shop = null): int { return 0; }
}

class Hook {
    public static function exec(string $hook_name, array $hook_args = [], ?int $id_module = null, ?bool $array_return = null, bool $check_exceptions = true, bool $use_push = false, ?int $id_shop = null): mixed { return null; }
}

class Shop {
    public const CONTEXT_ALL = 0;
    public int $id = 0;
    public function __construct(int $id = 0) {}
    public static function isFeatureActive(): bool { return false; }
    public static function setContext(int $context, ?int $id = null): void {}
    public static function getShops(bool $active = true, ?int $id_shop_group = null, bool $get_as_list_id = false): array { return []; }
}

class Db {
    public static function getInstance(bool $slave = false): static { return new static; }
    public function execute(string $sql, bool $use_cache = true): bool { return false; }
}

class Tab extends ObjectModel {
    public string $class_name = '';
    public string $module = '';
    public int $id_parent = 0;
    public array $name = [];
    public function __construct(int $id = 0) {}
    public function add(): bool { return false; }
    public static function getIdFromClassName(string $class_name, ?int $id_lang = null): int { return 0; }
}

class Language extends ObjectModel {
    public static function getLanguages(bool $active = true, bool|int $id_shop = false, bool $ids_only = false): array { return []; }
}

class CustomerSession {
    public function setCustomer(Customer $customer): void {}
}

class Context {
    public Customer $customer;
    public Cart $cart;
    public Shop $shop;
    public static function getContext(): static { return new static; }
}

define('_PS_MODULE_DIR_', '/var/www/html/modules/');
define('_DB_PREFIX_', 'ps_');

class HelperForm {
    public string $module = '';
    public string $name_controller = '';
    public string $token = '';
    public array $fields_value = [];
    public array $languages = [];
    public int $default_form_language = 1;
    public bool $allow_employee_form_lang = false;
    public string $title = '';
    public string $submit_action = '';
    public function generateForm(array $fields_form): string { return ''; }
}

class PrestaShopLogger {
    public static function addLog(string $message, int $severity = 1, ?int $error_code = null, ?string $object_type = null, ?int $object_id = null, bool $allow_duplicate = false, ?int $id_employee = null): void {}
}

class Link {
    public function getModuleLink(string $module, string $controller = 'default', array $params = [], ?bool $ssl = null, ?int $id_lang = null, ?int $id_shop = null): string { return ''; }
    public function getAdminLink(string $controller, bool $withToken = true, array $sfRouteParams = [], array $params = []): string { return ''; }
    public function getPageLink(string $controller, ?bool $ssl = null, ?int $id_lang = null, mixed $request = null, bool $request_url_encode = false, ?int $id_shop = null): string { return ''; }
}

class Employee {
    public int $id = 0;
}

class Currency {
    public string $sign = '';
    public string $iso_code = '';
    public static function getCurrent(): static { return new static; }
}

class Media {
    public static function getCSSPath(string $file_name, string $type = 'all', bool $check_path = true): string { return ''; }
    public static function getJSPath(string $file_name): string { return ''; }
}
class Product extends ObjectModel {
    public string $name = '';
    public string $link_rewrite = '';
    public int $id_category_default = 0;
    public function __construct(int $id = 0, bool $full = false, ?int $id_lang = null, ?int $id_shop = null) {}
    public function getLink(): string { return ''; }
}

class Category extends ObjectModel {
    public string $name = '';
    public string $link_rewrite = '';
    public function __construct(int $id = 0, ?int $id_lang = null, ?int $id_shop = null) {}
    public function getLink(): string { return ''; }
}

if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'InnoDB');
}
if (!defined('__PS_BASE_URI__')) {
    define('__PS_BASE_URI__', '/');
}

