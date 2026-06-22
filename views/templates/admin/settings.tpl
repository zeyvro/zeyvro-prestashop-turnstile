{*
 * Panel de administración — Turnstile Anti-Spam
 *
 * @author  Zeyvro <hola@zeyvro.com>
 * @license MIT
 * @link    https://zeyvro.com
 *}

{if $confirmations}
  {foreach $confirmations as $confirmation}
    <div class="alert alert-success">{$confirmation|escape:'htmlall':'UTF-8'}</div>
  {/foreach}
{/if}

{if $errors}
  {foreach $errors as $error}
    <div class="alert alert-danger">{$error|escape:'htmlall':'UTF-8'}</div>
  {/foreach}
{/if}

<div class="panel">
  <div class="panel-heading">
    <i class="icon-list"></i>
    {l s='Log de verificaciones (últimas 50)' mod='zeyvro_turnstile'}
    <span class="badge">{$log_total|escape:'htmlall':'UTF-8'}</span>
  </div>

  <div class="panel-body">
    {if $logs}
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead>
            <tr>
              <th>{l s='Fecha' mod='zeyvro_turnstile'}</th>
              <th>{l s='IP' mod='zeyvro_turnstile'}</th>
              <th>{l s='Resultado' mod='zeyvro_turnstile'}</th>
              <th>{l s='Score' mod='zeyvro_turnstile'}</th>
              <th>{l s='Errores' mod='zeyvro_turnstile'}</th>
              <th>{l s='User-Agent' mod='zeyvro_turnstile'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach $logs as $log}
              <tr class="{if $log.success}success{else}danger{/if}">
                <td>{$log.date_add|escape:'htmlall':'UTF-8'}</td>
                <td><code>{$log.ip|escape:'htmlall':'UTF-8'}</code></td>
                <td>
                  {if $log.success}
                    <span class="label label-success">{l s='OK' mod='zeyvro_turnstile'}</span>
                  {else}
                    <span class="label label-danger">{l s='FAIL' mod='zeyvro_turnstile'}</span>
                  {/if}
                </td>
                <td>{if $log.score !== null}{$log.score|escape:'htmlall':'UTF-8'}{else}—{/if}</td>
                <td>{if $log.error_codes}{$log.error_codes|escape:'htmlall':'UTF-8'}{else}—{/if}</td>
                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="{$log.user_agent|escape:'htmlall':'UTF-8'}">
                  {$log.user_agent|truncate:60:'...'|escape:'htmlall':'UTF-8'}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      </div>
    {else}
      <p class="text-muted">{l s='No hay registros todavía.' mod='zeyvro_turnstile'}</p>
    {/if}
  </div>

  <div class="panel-footer">
    <a href="{$clear_url|escape:'htmlall':'UTF-8'}"
       class="btn btn-default btn-sm"
       onclick="return confirm('{l s='¿Eliminar logs de más de 30 días?' mod='zeyvro_turnstile' js=1}');">
      <i class="icon-trash"></i>
      {l s='Limpiar logs >30 días' mod='zeyvro_turnstile'}
    </a>
    <small class="text-muted" style="margin-left:10px;">
      {l s='Total en BD: ' mod='zeyvro_turnstile'}{$log_total|escape:'htmlall':'UTF-8'}
    </small>
  </div>
</div>

