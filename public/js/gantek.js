document.documentElement.classList.add('js');

const sidebar = document.querySelector('.sidebar');
const workspace = document.querySelector('.workspace');
const toggle = document.querySelector('.menu-toggle');
const backdrop = document.querySelector('.menu-backdrop');
const mobile = window.matchMedia('(max-width: 760px)');
function setMenu(open) {
    document.documentElement.classList.toggle('menu-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    backdrop.hidden = !open;
    workspace.inert = open;
    sidebar.inert = mobile.matches && !open;
    if (open) sidebar.querySelector('[data-close-menu]').focus();
}
toggle.addEventListener('click', () => setMenu(true));
document.querySelectorAll('[data-close-menu]').forEach(button => button.addEventListener('click', () => {
    setMenu(false);
    toggle.focus();
}));
mobile.addEventListener('change', () => setMenu(false));
setMenu(false);
document.addEventListener('keydown', event => {
    if (!document.documentElement.classList.contains('menu-open')) return;
    if (event.key === 'Escape') { setMenu(false); toggle.focus(); }
    if (event.key === 'Tab') {
        const items = [...sidebar.querySelectorAll('a,button')].filter(item => item.getClientRects().length);
        if (event.shiftKey && document.activeElement === items[0]) { event.preventDefault(); items.at(-1).focus(); }
        else if (!event.shiftKey && document.activeElement === items.at(-1)) { event.preventDefault(); items[0].focus(); }
    }
});
document.querySelectorAll('[data-dismiss]').forEach(button => button.addEventListener('click', () => button.closest('.flash-message').remove()));

const dialog = document.querySelector('#confirm-dialog');
let pendingForm, pendingButton, approvedForm, returnFocus;
document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.busy) { event.preventDefault(); return; }
    if (form.dataset.confirmMessage && approvedForm !== form) {
        event.preventDefault();
        if (!dialog.showModal) {
            if (window.confirm(form.dataset.confirmName + '\n' + form.dataset.confirmMessage)) {
                approvedForm = form;
                form.requestSubmit(event.submitter || undefined);
            }
            return;
        }
        pendingForm = form;
        pendingButton = event.submitter;
        returnFocus = event.submitter || document.activeElement;
        dialog.querySelector('#confirm-title').textContent = form.dataset.confirmTitle;
        dialog.querySelector('#confirm-record').textContent = form.dataset.confirmName;
        dialog.querySelector('#confirm-description').textContent = form.dataset.confirmMessage;
        dialog.querySelector('[data-confirm]').textContent = form.dataset.confirmLabel;
        dialog.showModal();
        return;
    }
    approvedForm = null;
    if (form.method.toLowerCase() === 'post') {
        form.dataset.busy = 'true';
        if (event.submitter) {
            event.submitter.dataset.originalText = event.submitter.textContent;
            event.submitter.textContent = 'Guardando…';
            event.submitter.setAttribute('aria-disabled', 'true');
        }
    }
});
dialog.querySelectorAll('[data-cancel]').forEach(button => button.addEventListener('click', () => dialog.close()));
dialog.addEventListener('close', () => { returnFocus?.focus(); pendingForm = null; pendingButton = null; });
dialog.querySelector('[data-confirm]').addEventListener('click', () => {
    const form = pendingForm, button = pendingButton;
    if (!form) return;
    approvedForm = form;
    dialog.close();
    form.requestSubmit(button || undefined);
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-busy]').forEach(form => delete form.dataset.busy);
    document.querySelectorAll('[data-original-text]').forEach(button => {
        button.textContent = button.dataset.originalText;
        delete button.dataset.originalText;
        button.removeAttribute('aria-disabled');
    });
});

document.querySelectorAll('[data-field]').forEach(field => {
    const control = field.querySelector('input,select,textarea');
    const error = field.querySelector('[data-field-error]');
    if (!control || !error) return;
    let hasServerError = control.getAttribute('aria-invalid') === 'true';
    function showError() {
        let message = '';
        const validity = control.validity;
        if (validity.valueMissing) message = 'Completa este campo.';
        else if (validity.typeMismatch) message = 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
        else if (validity.rangeOverflow) message = 'El valor no puede ser mayor que ' + control.max + '.';
        else if (validity.rangeUnderflow) message = 'El valor debe ser igual o mayor que ' + control.min + '.';
        else if (validity.stepMismatch) message = 'Usa el incremento indicado: ' + control.step + '.';
        else if (validity.badInput) message = 'Escribe un número válido.';
        else if (!validity.valid) message = control.validationMessage;
        error.textContent = message;
        error.hidden = !message;
        control.setAttribute('aria-invalid', String(Boolean(message)));
    }
    control.addEventListener('invalid', showError);
    control.addEventListener('blur', () => { if (!hasServerError && (control.value || control.getAttribute('aria-invalid') === 'true')) showError(); });
    control.addEventListener('input', () => {
        hasServerError = false;
        if (control.getAttribute('aria-invalid') === 'true') showError();
    });
});
function linkDates(sourceName, targetName, attribute) {
    const source = document.getElementById(sourceName), target = document.getElementById(targetName);
    if (!source || !target) return;
    const update = () => { if (source.value) target.setAttribute(attribute, source.value); else target.removeAttribute(attribute); };
    source.addEventListener('change', update);
    update();
}
linkDates('fecha_aplicacion', 'proxima_aplicacion', 'min');
linkDates('fecha_ingreso', 'fecha_nacimiento', 'max');
document.querySelector('.validation-summary')?.focus();
