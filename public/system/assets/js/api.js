window.escapeSystemHtml = function (value) {
    const el = document.createElement('span');
    el.textContent = value ?? '';
    return el.innerHTML;
};
window.systemFetch = async function (url, options = {}) {
    const response = await fetch(url, {
        ...options, credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, ...options.headers },
    });
    if (!response.ok) {
        const body = await response.clone().json().catch(() => ({}));
        const message = Object.values(body.errors ?? {}).flat()[0] || body.message || 'Não foi possível salvar. Tente novamente.';
        let notice = document.getElementById('system-api-error');
        if (!notice) {
            notice = document.createElement('div'); notice.id = 'system-api-error';
            notice.className = 'system-alert system-alert--fixed'; notice.setAttribute('role', 'alert');
            document.body.append(notice);
        }
        notice.textContent = message;
        clearTimeout(window.systemErrorTimeout);
        window.systemErrorTimeout = setTimeout(() => notice.remove(), 7000);
        throw new Error(message);
    }
    return response;
};
