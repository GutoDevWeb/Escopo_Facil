document.addEventListener('DOMContentLoaded', function () {
    const features = JSON.parse(document.body.dataset.features || '[]');
    const lock = (selectors) => document.querySelectorAll(selectors).forEach(el => {
        el.hidden = true;
        el.querySelectorAll('input,select,textarea,button').forEach(input => { input.required = false; input.disabled = true; });
    });
    if (!features.includes('priorities')) {
        lock('.modal-priority,.priority-tag');
        document.querySelector('#detailPriority')?.closest('.card-detail-section')?.setAttribute('hidden', '');
    }
    if (!features.includes('deadlines')) {
        lock('.modal-date,.datetime-priority');
        document.querySelector('#detailPrazo')?.closest('.card-detail-section')?.setAttribute('hidden', '');
    }
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
