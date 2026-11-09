import QRCode from 'qrcode';

const dialog = document.getElementById('checkout-modal');
const root = document.getElementById('checkout');
if (dialog && root) {
    const get = id => root.querySelector(`#${id}`);
    const feedback = get('payment-feedback');
    const periods = [...root.querySelectorAll('[data-checkout-period]')];
    const format = cents => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100);
    let period = dialog.dataset.initialPeriod;
    let planSlug = dialog.dataset.initialPlan || 'pro';
    let options;
    let payment;
    let polling;
    let current = 'intro';
    let busy = false;
    let cycle = 0;
    let controller;
    let opener;
    let returnURL = root.dataset.home + '#precos';
    let historyClosing = false;
    let suspending = false;
    let awaitingAuth = false;
    const intentKey = 'escopo-checkout-intent';

    function saveIntent() {
        try { sessionStorage.setItem(intentKey, JSON.stringify({ period, planSlug, returnURL })); } catch { /* Optional. */ }
    }
    function clearIntent() {
        try { sessionStorage.removeItem(intentKey); } catch { /* Optional. */ }
    }
    function readIntent() {
        try {
            const intent = JSON.parse(sessionStorage.getItem(intentKey));
            if (!intent || !['monthly', 'yearly'].includes(intent.period)) return null;
            const url = new URL(intent.returnURL, location.href);
            if (url.origin !== location.origin) return null;
            if (intent.planSlug && !['pro', 'equipe'].includes(intent.planSlug)) return null;
            return { period: intent.period, planSlug: intent.planSlug || 'pro', returnURL: url.href };
        } catch { return null; }
    }

    function message(text = '') {
        feedback.textContent = text;
        feedback.hidden = !text;
    }
    function screen(state, focus = true) {
        const changed = state !== current;
        current = state;
        ['intro', 'loading', 'pending', 'expired', 'paid'].forEach(name => { get(`payment-${name}`).hidden = name !== state; });
        const heading = get(`payment-${state}`).querySelector('h2');
        dialog.setAttribute('aria-labelledby', heading?.id || 'payment-title');
        if (changed && focus && heading) heading.focus({ preventScroll: true });
    }
    function storageKey() { return `escopo-pix:${options?.email || 'guest'}:${options?.plan.id}:${period}`; }
    function save(id) {
        try { id ? sessionStorage.setItem(storageKey(), id) : sessionStorage.removeItem(storageKey()); } catch { /* Optional in private browsing. */ }
    }
    function updateSummary() {
        periods.forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.checkoutPeriod === period));
            button.disabled = current === 'paid' || busy;
        });
        root.dataset.period = period;
        if (options) {
            root.querySelector('[data-checkout-plan-name]').textContent = options.plan.name;
            get('payment-title').textContent = options.plan.slug === 'equipe' ? 'Seu próximo passo é Equipe' : 'Seu próximo passo é Pro';
            get('paid-title').textContent = options.plan.slug === 'equipe' ? 'Seu Equipe está liberado!' : 'Seu Pro está liberado!';
            get('payment-pending').querySelector('.checkout-description').textContent = `Conclua a simulação para começar a usar seu ${options.plan.name}.`;
            root.dataset.plan = options.plan.id;
            root.querySelector('[data-checkout-total]').textContent = format(payment?.amount ?? options.plan[period]);
            const email = root.querySelector('[data-checkout-email]');
            email.textContent = options.email || '';
            email.hidden = !options.email;
            const available = options.checkout_available !== false;
            get('generate-pix').hidden = !options.verified || !available;
            get('payment-login').hidden = options.authenticated;
            get('payment-verify').hidden = !options.authenticated || options.verified;
            get('payment-check-verification').hidden = !options.authenticated || options.verified;
            if (!available) {
                ['payment-login', 'payment-verify', 'payment-check-verification'].forEach(id => { get(id).hidden = true; });
                message('A contratação online ainda não está disponível. Fale com nossa equipe pelo contato do site.');
            }
        }
        root.querySelector('[data-checkout-term]').textContent = period === 'yearly' ? '12 meses de acesso. Pagamento único.' : '1 mês de acesso. Pagamento único.';
    }
    function syncSession(data) {
        if (data.csrf) {
            document.querySelector('meta[name=csrf-token]').content = data.csrf;
            document.querySelectorAll('#login-modal [name=_token]').forEach(input => { input.value = data.csrf; });
        }
        const auth = document.getElementById('login-modal');
        auth.dataset.authenticated = String(data.authenticated);
        auth.dataset.verified = String(data.verified);
    }
    async function request(url, method = 'GET', data) {
        const response = await fetch(url, {
            method, credentials: 'same-origin', signal: controller?.signal,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            ...(data ? { body: JSON.stringify(data) } : {}),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const failure = new Error(response.status === 419 ? 'Sua sessão expirou. Atualize a página para continuar.'
                : response.status === 401 ? 'Entre na sua conta para continuar.'
                : response.status === 429 ? 'Aguarde alguns instantes antes de tentar novamente.'
                : Object.values(result.errors || {}).flat()[0] || result.message || 'Não foi possível concluir. Tente novamente.');
            failure.status = response.status;
            throw failure;
        }
        return result;
    }
    function showFailure(error) {
        if (error.name === 'AbortError') return;
        message(error instanceof TypeError ? 'Não foi possível conectar. Verifique sua conexão e tente novamente.' : error.message);
        if (error.status === 401 && options) {
            options = { ...options, authenticated: false, verified: false, email: null };
            syncSession(options);
            screen('intro');
            updateSummary();
        }
    }
    async function render(data, generation = cycle) {
        if (generation !== cycle || !dialog.open) return;
        payment = data;
        period = data.period;
        clearTimeout(polling);
        if (data.status === 'paid') { screen('paid'); save(null); updateSummary(); return; }
        if (['expired', 'cancelled', 'failed'].includes(data.status)) { screen('expired'); save(null); updateSummary(); return; }
        await QRCode.toCanvas(get('pix-qr'), data.code, { width: 240, margin: 2, color: { dark: '#20201f', light: '#ffffff' } });
        if (generation !== cycle || !dialog.open) return;
        get('pix-code').value = data.code;
        get('copy-pix').textContent = 'Copiar código';
        screen('pending');
        save(data.id);
        updateSummary();
        countdown();
        polling = setTimeout(check, 6000);
    }
    async function restore(generation) {
        if (!options.verified) return;
        let id;
        try { id = sessionStorage.getItem(storageKey()); } catch { /* Optional. */ }
        if (!id || !/^[0-9a-f-]{36}$/i.test(id)) return;
        try { await render(await request(`${root.dataset.statusBase}/${id}`), generation); }
        catch (error) {
            if (generation !== cycle) return;
            if ([404, 409].includes(error.status)) save(null);
            else showFailure(error);
        }
    }
    async function open(selected = 'yearly', historyMode = 'push', selectedPlan = planSlug) {
        planSlug = ['pro', 'equipe'].includes(selectedPlan) ? selectedPlan : 'pro';
        selected = ['monthly', 'yearly'].includes(selected) ? selected : 'yearly';
        if (!dialog.open) opener = document.activeElement;
        if (historyMode === 'push' && !dialog.open && !location.pathname.startsWith('/assinar/') && !location.pathname.startsWith('/checkout/')) returnURL = location.href;
        controller?.abort();
        controller = new AbortController();
        const generation = ++cycle;
        clearTimeout(polling);
        busy = false;
        root.querySelectorAll('button').forEach(button => { button.disabled = false; button.removeAttribute('aria-busy'); });
        period = selected;
        options = null;
        payment = null;
        message();
        if (!dialog.open) dialog.showModal();
        document.documentElement.classList.add('checkout-modal-open');
        screen('loading', false);
        dialog.querySelector('[data-modal-scroll]').scrollTop = 0;
        updateSummary();
        const url = new URL(`assinar/${planSlug}`, root.dataset.home.endsWith('/') ? root.dataset.home : root.dataset.home + '/');
        url.searchParams.set('period', period);
        if (historyMode === 'push') history.pushState({ checkout: true, period, planSlug }, '', url);
        else if (historyMode === 'replace') history.replaceState({ checkout: true, period, planSlug }, '', url);
        try {
            const optionsURL = new URL(root.dataset.optionsUrl);
            optionsURL.pathname = optionsURL.pathname.replace(/\/[^/]+$/, `/${planSlug}`);
            const data = await request(optionsURL.href);
            if (generation !== cycle || !dialog.open) return;
            options = data;
            syncSession(data);
            screen('intro');
            updateSummary();
            if (data.checkout_available === false) return;
            if (!data.authenticated) {
                // Paint the payment summary before opening the shared login modal.
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    if (generation === cycle && dialog.open) authenticate('login');
                }));
                return;
            }
            if (data.verified) clearIntent();
            await restore(generation);
        } catch (error) {
            if (generation !== cycle) return;
            screen('intro');
            get('generate-pix').disabled = true;
            showFailure(error);
        }
    }
    async function action(button, callback) {
        if (busy) return;
        const generation = cycle;
        busy = true;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        periods.forEach(option => { option.disabled = true; });
        message();
        try { await callback(generation); }
        catch (error) { if (generation === cycle) showFailure(error); }
        finally {
            if (generation === cycle) {
                busy = false;
                button.disabled = false;
                button.removeAttribute('aria-busy');
                updateSummary();
                countdown();
            }
        }
    }
    function generate(button) {
        action(button, async generation => {
            clearTimeout(polling);
            await render(await request(root.dataset.endpoint, 'POST', { plan_id: options.plan.id, period }), generation);
        });
    }
    async function check() {
        clearTimeout(polling);
        if (!payment || current !== 'pending' || !dialog.open) return;
        if (document.hidden || busy) { polling = setTimeout(check, 6000); return; }
        const generation = cycle;
        try { await render(await request(payment.status_url), generation); }
        catch (error) {
            if (generation !== cycle) return;
            showFailure(error);
            if (![401, 403, 404, 419].includes(error.status)) polling = setTimeout(check, 10000);
        }
    }
    function countdown() {
        if (!payment || current !== 'pending' || !dialog.open) return;
        const remaining = Math.max(0, Math.ceil((Date.parse(payment.expires_at) - Date.now()) / 1000));
        get('pix-countdown').textContent = `${String(Math.floor(remaining / 60)).padStart(2, '0')}:${String(remaining % 60).padStart(2, '0')}`;
        get('simulate-pix').disabled = remaining === 0 || busy;
    }
    function authenticate(screen) {
        awaitingAuth = true;
        saveIntent();
        suspending = true;
        dialog.close();
        window.EscopoAuthModal.open(screen);
    }
    window.addEventListener('escopo:auth-response', event => {
        if (!awaitingAuth || !event.detail.authenticated || !event.detail.verified) return;
        event.preventDefault();
        awaitingAuth = false;
        clearIntent();
        window.EscopoAuthModal.close();
        open(period, 'replace');
    });
    window.addEventListener('escopo:auth-closed', event => {
        if (event.detail?.programmatic) return;
        awaitingAuth = false;
        clearIntent();
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-checkout-link]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank') return;
        event.preventDefault();
        const url = new URL(link.href);
        open(url.searchParams.get('period'), 'push', url.pathname.split('/').pop());
    });
    root.querySelectorAll('[data-checkout-period]').forEach(button => button.addEventListener('click', () => {
        if (!busy && period !== button.dataset.checkoutPeriod) open(button.dataset.checkoutPeriod, 'replace');
    }));
    dialog.querySelector('[data-close-checkout]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        // Native close events are queued; the dialog may already have reopened.
        if (dialog.open) return;
        controller?.abort();
        clearTimeout(polling);
        cycle++;
        document.documentElement.classList.remove('checkout-modal-open');
        if (!historyClosing && !suspending) history.replaceState({}, '', returnURL);
        if (!suspending) opener?.focus({ preventScroll: true });
        historyClosing = false;
        suspending = false;
    });
    window.addEventListener('popstate', event => {
        if (event.state?.checkout) open(event.state.period, 'none', event.state.planSlug || 'pro');
        else if (dialog.open) { historyClosing = true; dialog.close(); }
    });
    get('generate-pix').addEventListener('click', event => generate(event.currentTarget));
    get('retry-pix').addEventListener('click', event => generate(event.currentTarget));
    get('check-pix').addEventListener('click', check);
    get('payment-login').addEventListener('click', () => authenticate('login'));
    get('payment-verify').addEventListener('click', () => authenticate('verify-email'));
    get('payment-check-verification').addEventListener('click', () => open(period, 'replace'));
    get('simulate-pix').addEventListener('click', event => action(event.currentTarget, async generation => {
        await render(await request(payment.simulate_url, 'POST'), generation);
    }));
    get('copy-pix').addEventListener('click', event => action(event.currentTarget, async () => {
        try { await navigator.clipboard.writeText(payment.code); event.target.textContent = 'Código copiado ✓'; }
        catch { get('pix-code').focus(); get('pix-code').select(); message('Selecione e copie o código acima.'); }
    }));
    setInterval(countdown, 1000);
    dialog.dataset.checkoutReady = 'true';
    function resume() {
        const intent = readIntent();
        const auth = document.getElementById('login-modal');
        if (dialog.dataset.initialOpen === 'true') {
            open(period, 'replace');
        } else if (intent) {
            period = intent.period;
            planSlug = intent.planSlug;
            returnURL = intent.returnURL;
            awaitingAuth = true;
            if (auth.dataset.authenticated === 'true' && auth.dataset.verified === 'true') {
                awaitingAuth = false;
                clearIntent();
                window.EscopoAuthModal.close();
                open(period, 'replace');
            } else if (!auth.open) {
                window.EscopoAuthModal.open(auth.dataset.authenticated === 'true' ? 'verify-email' : 'login');
            }
        }
    }
    if (window.EscopoAuthModal) resume();
    else document.addEventListener('DOMContentLoaded', resume, { once: true });
}
