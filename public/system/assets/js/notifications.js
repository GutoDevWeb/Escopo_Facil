(() => {
    const toggle = document.querySelector('[data-notification-toggle]');
    const panel = document.getElementById('system-notifications');
    if (!toggle || !panel) return;
    const list = panel.querySelector('[data-notification-list]');
    const status = panel.querySelector('[data-notification-status]');
    const dot = toggle.querySelector('[data-notification-dot]');
    let generation = 0;
    let timer;
    let marking = false;
    const queued = new Set();
    const date = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    function count(value) {
        dot.hidden = value === 0;
        toggle.setAttribute('aria-label', value ? `Notificações, ${value} não visualizadas` : 'Notificações');
        status.textContent = value ? `${value} não ${value === 1 ? 'visualizada' : 'visualizadas'}` : 'Você está em dia';
    }
    function position() {
        const rect = toggle.getBoundingClientRect();
        const viewport = window.visualViewport;
        const leftEdge = viewport?.offsetLeft || 0;
        const topEdge = viewport?.offsetTop || 0;
        const width = viewport?.width || innerWidth;
        const height = viewport?.height || innerHeight;
        const margin = 12;
        panel.style.width = `${Math.max(0, Math.min(380, width - margin * 2))}px`;
        const panelWidth = panel.getBoundingClientRect().width;
        const left = Math.max(leftEdge + margin, Math.min(rect.right - panelWidth, leftEdge + width - panelWidth - margin));
        const top = Math.max(topEdge + margin, Math.min(rect.bottom + 10, topEdge + height - 120));
        panel.style.left = `${left}px`;
        panel.style.right = 'auto';
        panel.style.top = `${top}px`;
        panel.style.maxHeight = `calc(${Math.max(0, topEdge + height - top - margin)}px - env(safe-area-inset-bottom, 0px))`;
    }
    async function markViewed() {
        if (marking || panel.hidden || !queued.size) return;
        marking = true;
        const ids = [...queued]; queued.clear();
        try {
            const data = await (await systemFetch(panel.dataset.notificationsReadUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids }),
            })).json();
            count(data.unread_count);
            data.read_ids.forEach(id => {
                const item = list.querySelector(`[data-notification-id="${CSS.escape(id)}"]`);
                if (item) { item.classList.remove('is-unread'); item.dataset.read = 'true'; }
            });
        } catch { ids.forEach(id => queued.add(id)); status.textContent = 'Não foi possível marcar como visualizada. Abra novamente para tentar.'; }
        finally { marking = false; }
        if (queued.size && !panel.hidden) timer = setTimeout(markViewed, 1500);
    }
    // Mark only entries that actually became visible in the open list.
    const observer = new IntersectionObserver(entries => {
        if (panel.hidden || document.hidden) return;
        for (const entry of entries) {
            if (entry.isIntersecting && entry.intersectionRatio >= .5 && entry.target.dataset.read !== 'true') {
                queued.add(entry.target.dataset.notificationId);
                observer.unobserve(entry.target);
            }
        }
        clearTimeout(timer); timer = setTimeout(markViewed, 150);
    }, { root: list, threshold: .5 });
    function close(focus = false) {
        panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); generation++;
        observer.disconnect(); clearTimeout(timer); queued.clear();
        if (focus) toggle.focus();
    }
    async function open() {
        const current = ++generation;
        panel.hidden = false; toggle.setAttribute('aria-expanded', 'true'); position();
        status.textContent = 'Carregando…'; list.replaceChildren();
        panel.querySelector('h2').focus({ preventScroll: true });
        try {
            const data = await (await systemFetch(panel.dataset.notificationsUrl)).json();
            if (current !== generation || panel.hidden) return;
            count(data.unread_count); observer.disconnect();
            if (!data.items.length) {
                const empty = document.createElement('p'); empty.className = 'system-notifications-empty';
                empty.textContent = 'Nenhuma notificação por aqui ainda.'; list.append(empty);
            }
            for (const item of data.items) {
                const article = document.createElement('article');
                article.className = `system-notification-item${item.read ? '' : ' is-unread'}`;
                article.dataset.notificationId = item.id; article.dataset.read = String(item.read);
                const title = document.createElement('h3'); title.textContent = item.title;
                const body = document.createElement('p'); body.textContent = item.body;
                const time = document.createElement('time'); time.dateTime = item.created_at; time.textContent = date.format(new Date(item.created_at));
                article.append(title, body, time); list.append(article);
                if (!item.read) observer.observe(article);
            }
        } catch { if (current === generation) status.textContent = 'Não foi possível carregar. Feche e tente novamente.'; }
    }
    toggle.addEventListener('click', () => panel.hidden ? open() : close());
    panel.querySelector('[data-notification-close]').addEventListener('click', () => close(true));
    document.addEventListener('click', e => { if (!panel.hidden && !panel.contains(e.target) && !toggle.contains(e.target)) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden) { e.preventDefault(); close(true); } });
    window.addEventListener('resize', () => { if (!panel.hidden) position(); });
    window.visualViewport?.addEventListener('resize', () => { if (!panel.hidden) position(); });
    window.visualViewport?.addEventListener('scroll', () => { if (!panel.hidden) position(); });
    document.querySelector('.main')?.addEventListener('scroll', () => { if (!panel.hidden) position(); }, { passive: true });
    setInterval(async () => {
        if (document.hidden || !panel.hidden) return;
        try {
            const response = await fetch(panel.dataset.notificationsUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            if (response.ok) count((await response.json()).unread_count);
        } catch { /* Keep the last known count when offline. */ }
    }, 60000);
})();
