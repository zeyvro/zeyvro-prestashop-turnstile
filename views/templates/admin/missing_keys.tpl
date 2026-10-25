{*
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
 *}

{if $missing_keys}
  <div class="alert alert-danger zeyvro-turnstile-missing-keys">
    {foreach $missing_keys as $line}
      <p><strong>{$line|escape:'htmlall':'UTF-8'}</strong></p>
    {/foreach}
  </div>
{/if}
