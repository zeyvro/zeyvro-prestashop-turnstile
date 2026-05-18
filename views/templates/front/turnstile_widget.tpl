{**
 * Turnstile widget injection template.
 * Renderizado via hookDisplayBeforeBodyClosingTag solo en la página de contacto.
 *}

<div id="sbt-placeholder"
     data-sitekey="{$sbt_site_key|escape:'htmlall':'UTF-8'}"
     data-mode="{$sbt_mode|escape:'html':'UTF-8'}"
     style="display:none;"
     aria-hidden="true"></div>

{literal}
<script>
(function () {
    function sbtInjectWidget() {
        var placeholder = document.getElementById('sbt-placeholder');
        if (!placeholder) { return; }
        var siteKey = placeholder.getAttribute('data-sitekey');
        var mode    = placeholder.getAttribute('data-mode') || 'managed';

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

        if (placeholder.parentNode) { placeholder.parentNode.removeChild(placeholder); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sbtInjectWidget);
    } else {
        sbtInjectWidget();
    }
})();
</script>
{/literal}
