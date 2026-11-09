(() => {
    const media = window.matchMedia('(max-width: 900px)');
    const sidebar = document.querySelector('#system-sidebar');
    const trigger = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const main = document.querySelector('main.main');
    let open = false;
    function setOpen(value, restoreFocus = true) {
        open = value && media.matches;
        document.documentElement.classList.toggle('sidebar-open', open);
        trigger.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        sidebar.inert = media.matches && !open;
        main.inert = open;
        document.querySelector('.system-mobile-header').inert = open;
        if (open) sidebar.querySelector('[data-sidebar-close]').focus();
        else if (restoreFocus && media.matches) trigger.focus();
    }
    trigger.addEventListener('click', () => setOpen(!open));
    document.querySelectorAll('[data-sidebar-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false, false)));
    document.addEventListener('keydown', event => {
        if (!open || document.querySelector('dialog[open]')) return;
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key === 'Tab') {
            const items = Array.from(sidebar.querySelectorAll('a,button,input,select,textarea,[tabindex="0"]')).filter(item => !item.disabled && item.getClientRects().length);
            const first = items[0], last = items.at(-1);
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    media.addEventListener('change', () => setOpen(false, false));
    setOpen(false, false);
})();
