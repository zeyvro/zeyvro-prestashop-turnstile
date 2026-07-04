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
global $_MODULE;
$_MODULE = [];

$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_5527b6b01dcf35691545691a8a79e27c'] = 'Zeyvro Turnstile';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_c1fef78503f22ff160104319a0e62f49'] = 'Anti-spam protection with Cloudflare Turnstile on the PrestaShop contact form.';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_4827ecb3ad37d0623827e275419c288f'] = 'Verificación de seguridad fallida. Por favor, inténtalo de nuevo.';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_f4b30c2fe83dab2f1db5ce70f7253749'] = 'Log de verificaciones (últimas 50)';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_44749712dbec183e983dcd78a7736c41'] = 'Fecha';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_a12a3079e14ced46e69ba52b8a90b21a'] = 'IP';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_8eea62084ca7e541d918e823422bd82e'] = 'Resultado';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_5dd135d1bcfa7f63c3b7f25425c2a4a1'] = 'Score';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_5ef0c737746fae2ca90e66c39333f8f6'] = 'Errores';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_fb831f965a1e3f3ee3af2b3c2de8be12'] = 'User-Agent';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_e0aa021e21dddbd6d8cecec71e9cf564'] = 'OK';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_c2759effffc94bb9acc71d69fe3e8a1f'] = 'FAIL';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_541e621d064f921e412ba26fdfaeb340'] = 'No hay registros todavía.';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_1f9dc37fa79b57b208c8738ec3d5fc17'] = '¿Eliminar logs de más de 30 días?';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_6e2699df1ac68a5e4b5a19a49e40aaf5'] = 'Limpiar logs >30 días';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_8db3a00357917cdbc16e6f0aed3cc6e7'] = 'Total en BD: ';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_5527b6b01dcf35691545691a8a79e27c'] = 'Zeyvro Turnstile';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_4d9ac946f92f7211e40671513c72fd09'] = 'Configuración guardada correctamente.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_889b11f5b4ccb2d7e1250c3caa06bb0b'] = 'Logs de más de 30 días eliminados.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_454edc9be634c7b2c4ba54e4b7c6ed33'] = 'Activar Turnstile';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_93cba07454f06a4a960172bbd6e2a435'] = 'Sí';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_bafd7322c6e97d25b6299b5d6fe8920b'] = 'No';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_778d3a70011a498691c7d8ac60614d3e'] = 'Desactívalo para deshabilitar temporalmente sin desinstalar.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_20f4f54341e68e01b853e9483e5fe37f'] = 'Site Key';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_ead79a6912471a8463bc2fec27f22938'] = 'Clave pública del widget. Deja en blanco al guardar para conservar el valor actual.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_5eb6bb157528b365f84c27bb4784031b'] = 'Secret Key';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_56f972dff9fddd8b1a9e9612148bdee0'] = 'Clave secreta para verificar el token en el servidor. Nunca la expongas. Deja este campo en blanco al guardar para conservar el valor actual.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_184af9cc551f1ae7caae31218f3042f4'] = 'Modo del widget';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_f5781ffd095ab28cadcd547f41425f87'] = 'Managed (automático, recomendado)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_ea84f7db5ea8141a5153f20f35db533e'] = 'Non-interactive (sin clic)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_c3a2a45097ab2de4ff7ca678fbec804f'] = 'Invisible (sin widget visible)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_683a26f26cf549e4f4e731b0f8631314'] = 'Acción si falla la verificación';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_6891a3f74bd4c450824bad73f4d9274b'] = 'Bloquear envío (recomendado)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_7a20ed61b7c5b81b0a3b4a201ca94ca6'] = 'Solo registrar (dejar pasar)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_44d1d09fdaf4f1c7d6f021450b6309ae'] = 'En "solo registrar" el formulario se envía aunque falle Turnstile.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_88a5a260fa93332ec4113fc43da4a5e3'] = 'Registrar intentos';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_65b51da33be9db94bb055ac46507b645'] = 'Timeout API (segundos)';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_fd4910c89b092bf6215e4a7ce2d89083'] = 'Tiempo máximo de espera para la llamada a Cloudflare. Por defecto: 5.';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_fb8e7b410a98236f1b530282166babcf'] = 'Configuración de Turnstile';
$_MODULE['<{zeyvro_turnstile}prestashop>adminzeyvroturnstilecontroller_c9cc8cce247e49bae79f15173ce97354'] = 'Guardar';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_f4b30c2fe83dab2f1db5ce70f7253749'] = 'Log de verificaciones (últimas 50)';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_44749712dbec183e983dcd78a7736c41'] = 'Fecha';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_a12a3079e14ced46e69ba52b8a90b21a'] = 'IP';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_8eea62084ca7e541d918e823422bd82e'] = 'Resultado';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_5dd135d1bcfa7f63c3b7f25425c2a4a1'] = 'Score';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_5ef0c737746fae2ca90e66c39333f8f6'] = 'Errores';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_fb831f965a1e3f3ee3af2b3c2de8be12'] = 'User-Agent';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_e0aa021e21dddbd6d8cecec71e9cf564'] = 'OK';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_c2759effffc94bb9acc71d69fe3e8a1f'] = 'FAIL';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_541e621d064f921e412ba26fdfaeb340'] = 'No hay registros todavía.';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_1f9dc37fa79b57b208c8738ec3d5fc17'] = '¿Eliminar logs de más de 30 días?';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_6e2699df1ac68a5e4b5a19a49e40aaf5'] = 'Limpiar logs >30 días';
$_MODULE['<{zeyvro_turnstile}prestashop>settings_8db3a00357917cdbc16e6f0aed3cc6e7'] = 'Total en BD: ';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_212648795ee66b21e544548fc3e0c896'] = 'Módulos premium para PrestaShop 8 — sin suscripciones, sin ataduras. Código limpio, pago único.';
$_MODULE['<{zeyvro_turnstile}prestashop>zeyvro_turnstile_e1a028559f9f53b63f6b88387a29d91d'] = 'Descúbrelos →';
