document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-dismiss-plan-overview]').forEach(button => {
        button.addEventListener('click', () => {
            button.closest('.plan-overview').hidden = true;
            document.querySelector('.search-input-wrapper input')?.focus({ preventScroll: true });
        });
    });
    const features = JSON.parse(document.body.dataset.features || '[]');
    const lock = (selectors) => document.querySelectorAll(selectors).forEach(el => {
        el.hidden = true;
        el.querySelectorAll('input,select,textarea,button').forEach(input => { input.required = false; input.disabled = true; });
    });
    const lockIcon = '<svg class="feature-lock-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="4" y="8" width="12" height="9" rx="2"/><path d="M6.5 8V5.5a3.5 3.5 0 0 1 7 0V8"/><path d="M10 11v3"/></svg>';
    const restrict = element => {
        if (!element) return;
        element.hidden = false;
        element.classList.add('feature-is-locked');
        element.setAttribute('aria-disabled', 'true');
        element.title = 'Disponível a partir do plano Pro';
        element.querySelectorAll('input,select,textarea,button').forEach(input => { input.required = false; input.disabled = true; });
        const label = element.querySelector('label,.card-detail-label');
        if (label && !label.querySelector('.feature-lock-icon')) label.insertAdjacentHTML('beforeend', lockIcon);
    };
    const lockedSummary = (selector, label) => document.querySelectorAll(selector).forEach(element => {
        restrict(element);
        element.replaceChildren();
        element.insertAdjacentHTML('afterbegin', lockIcon);
        element.append(document.createTextNode(label + ' · Pro'));
    });
    if (!features.includes('priorities')) {
        document.querySelectorAll('.modal-priority').forEach(restrict);
        restrict(document.querySelector('#detailPriority')?.closest('.card-detail-section'));
        lockedSummary('.priority-tag', 'Prioridade');
    }
    if (!features.includes('deadlines')) {
        document.querySelectorAll('.modal-date').forEach(restrict);
        restrict(document.querySelector('#detailPrazo')?.closest('.card-detail-section'));
        lockedSummary('.datetime-priority', 'Prazo');
    }
    document.addEventListener('click', event => {
        if (event.target.closest('.feature-is-locked .custom-select')) {
            event.preventDefault(); event.stopImmediatePropagation();
        }
    }, true);
    if (document.body.dataset.canWrite === 'false') {
        lock('.btn-new-task-main,.btn-add-card,.card-detail-actions,.checklist-add');
        document.querySelectorAll('.task-card').forEach(el => el.draggable = false);
        document.querySelectorAll('#cardDetailOverlay input,#cardDetailOverlay textarea').forEach(el => el.readOnly = true);
        lock('.checklist-add-row');
        document.addEventListener('click', e => {
            if (e.target.closest('.checklist-delete-btn,.checklist-check,.custom-select-trigger')) e.stopImmediatePropagation();
        }, true);
        document.addEventListener('change', e => {
            if (e.target.matches('.checklist-check')) { e.preventDefault(); e.stopImmediatePropagation(); e.target.checked = !e.target.checked; }
        }, true);
    }
});
