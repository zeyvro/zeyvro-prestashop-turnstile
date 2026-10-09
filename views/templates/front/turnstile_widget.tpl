{*
 * Turnstile widget injection template.
 * Renderizado via hookDisplayBeforeBodyClosingTag. Contacto como en la 1.1.10; desde la 1.2.0 también registro,
 * login (incluida la ventana de login de los temas, en cualquier página), recuperar contraseña, comentarios de
 * prestablog y avisos de reposición de ps_emailalerts, cada uno con su interruptor.
 *
 * @author  Zeyvro <hola@zeyvro.com>
 * @license MIT
 * @link    https://zeyvro.com
 *}

<div id="sbt-placeholder"
     data-sitekey="{$sbt_site_key|escape:'htmlall':'UTF-8'}"
     data-mode="{$sbt_mode|escape:'html':'UTF-8'}"
     {foreach from=$sbt_forms key=sbtForm item=sbtOn}{if $sbtOn}data-sbt-{$sbtForm|escape:'html':'UTF-8'}="1" {/if}{/foreach}
     data-sbt-page="{$sbt_page|escape:'html':'UTF-8'}"
     data-sbt-auth-url="{$sbt_auth_url|escape:'htmlall':'UTF-8'}"
     data-sbt-failed="{$sbt_failed|escape:'html':'UTF-8'}"
     data-sbt-error="{$sbt_error|escape:'htmlall':'UTF-8'}"
     style="display:none;"
     aria-hidden="true"></div>

{literal}
<script>
(function () {
    var API = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    var placeholder, siteKey, mode, page, authUrl, failed, errorText;
    var pending = [];
    var loading = false;

    function on(form) {
        return placeholder.getAttribute('data-sbt-' + form) === '1';
    }

    // ── Contacto: igual que en la 1.1.10 (render implícito; api.js en <head> desde hookDisplayHeader) ──
    function sbtInjectContact() {
        // Detectar forms de contacto por inputs característicos del módulo contactform PS8
        var contactForms = [];
        document.querySelectorAll('form').forEach(function (f) {
            if (f.querySelector('textarea[name="message"]')
                && f.querySelector('input[name="from"]')
                && f.querySelector('select[name="id_contact"]')
                && f.querySelector('button[name="submitMessage"], input[name="submitMessage"]')) {
                contactForms.push(f);
            }
        });
        if (contactForms.length === 0) { return; }

        contactForms.forEach(function (form) {
            // No duplicar si ya hay un widget en ESTE form
            if (form.querySelector('.cf-turnstile')) { return; }

            var widget = document.createElement('div');
            widget.className = 'cf-turnstile sbt-turnstile-widget';
            widget.style.marginBottom = '12px';
            widget.setAttribute('data-sitekey', siteKey);
            widget.setAttribute('data-theme', 'auto');
            if (mode === 'non-interactive') {
                widget.setAttribute('data-appearance', 'always');
                widget.setAttribute('data-execution', 'render');
            } else if (mode === 'invisible') {
                widget.setAttribute('data-appearance', 'interaction-only');
                widget.setAttribute('data-execution', 'render');
                widget.setAttribute('data-size', 'invisible');
            }

            var btn = form.querySelector('button[name="submitMessage"]')
                   || form.querySelector('button[type="submit"]')
                   || form.querySelector('input[type="submit"]');
            if (btn) {
                btn.parentNode.insertBefore(widget, btn);
            } else {
                form.appendChild(widget);
            }
        });
    }

    // ── v1.2.0: resto de formularios. Render explícito; api.js se carga aquí si la página no lo trae ya ──
    function renderPending() {
        if (!window.turnstile || typeof window.turnstile.render !== 'function') { return false; }
        while (pending.length) {
            var el = pending.shift();
            if (el.getAttribute('data-sbt-wid') !== null) { continue; }
            var params = { sitekey: siteKey, theme: 'auto' };
            if (mode === 'non-interactive') {
                params.appearance = 'always';
            } else if (mode === 'invisible') {
                params.appearance = 'interaction-only';
            }
            try {
                var wid = window.turnstile.render(el, params);
                el.setAttribute('data-sbt-wid', wid === undefined || wid === null ? '' : String(wid));
            } catch (e) {}
        }
        return true;
    }

    function loadApi() {
        if (renderPending() || loading) { return; }
        loading = true;
        if (!document.querySelector('script[src^="' + API + '"]')) {
            var s = document.createElement('script');
            s.src = API + '?render=explicit';
            s.async = true;
            s.defer = true;
            document.head.appendChild(s);
        }
        var tries = 0;
        var timer = setInterval(function () {
            if (renderPending() || ++tries > 150) {
                clearInterval(timer);
                loading = false;
            }
        }, 200);
    }

    function addWidget(box, before, form, lazy) {
        if (box.querySelector('.sbt-turnstile-widget, .cf-turnstile')) { return null; }
        var widget = document.createElement('div');
        widget.className = 'sbt-turnstile-widget';
        widget.setAttribute('data-sbt-form', form);
        widget.style.marginBottom = '12px';
        if (before && before.parentNode) {
            before.parentNode.insertBefore(widget, before);
        } else {
            box.appendChild(widget);
        }
        if (failed.indexOf(form) !== -1) {
            var error = document.createElement('div');
            error.className = 'alert alert-danger sbt-turnstile-error';
            error.setAttribute('role', 'alert');
            error.textContent = errorText;
            widget.parentNode.insertBefore(error, widget);
        }
        pending.push(widget);
        if (!lazy) {
            loadApi();
            return widget;
        }
        // Ventana de login fuera de la página de login: api.js solo cuando el visitante va a usarla
        var started = false;
        var start = function () {
            if (!started) {
                started = true;
                loadApi();
            }
        };
        box.addEventListener('focusin', start);
        box.addEventListener('mousedown', start);
        box.addEventListener('touchstart', start);
        return widget;
    }

    function submitButton(form) {
        return form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
    }

    // true = el formulario envía a esa URL (misma ruta y mismo ?controller= si no hay URL amigables)
    function postsTo(form, url) {
        try {
            var a = new URL(form.getAttribute('action') || window.location.href, window.location.href);
            var b = new URL(url, window.location.href);
            return a.pathname === b.pathname
                && (a.searchParams.get('controller') || '') === (b.searchParams.get('controller') || '');
        } catch (e) {
            return false;
        }
    }

    function sbtInjectForms() {
        document.querySelectorAll('form').forEach(function (form) {
            if (on('register') && form.querySelector('[name="submitCreate"]')) {
                addWidget(form, submitButton(form) || form.querySelector('[name="submitCreate"]'), 'register', false);
                return;
            }
            // (formularios v1.2.0)
        });
        // (fuera de un <form>, v1.2.0)
    }

    function sbtInit() {
        placeholder = document.getElementById('sbt-placeholder');
        if (!placeholder) { return; }
        siteKey = placeholder.getAttribute('data-sitekey');
        mode = placeholder.getAttribute('data-mode') || 'managed';
        page = placeholder.getAttribute('data-sbt-page') || '';
        authUrl = placeholder.getAttribute('data-sbt-auth-url') || '';
        failed = (placeholder.getAttribute('data-sbt-failed') || '').split(',');
        errorText = placeholder.getAttribute('data-sbt-error') || '';

        if (on('contact')) {
            sbtInjectContact();
        }
        sbtInjectForms();

        if (placeholder.parentNode) { placeholder.parentNode.removeChild(placeholder); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sbtInit);
    } else {
        sbtInit();
    }
})();
</script>
{/literal}
