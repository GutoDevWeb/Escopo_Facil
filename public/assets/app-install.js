(() => {
    let pendingPrompt = null;
    const standalone = window.matchMedia('(display-mode: standalone)');
    const installed = () => standalone.matches || navigator.standalone === true;
    const sync = () => document.querySelectorAll('[data-app-install]').forEach(button => { button.hidden = installed(); });
    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        pendingPrompt = event;
    });
    window.addEventListener('appinstalled', () => { pendingPrompt = null; document.querySelectorAll('[data-app-install]').forEach(button => { button.hidden = true; }); });
    standalone.addEventListener('change', sync);
    document.addEventListener('DOMContentLoaded', sync);
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-app-install]');
        if (!button) return;
        if (pendingPrompt) {
            const prompt = pendingPrompt;
            pendingPrompt = null;
            try { await prompt.prompt(); await prompt.userChoice; } catch { showHelp(); }
        } else showHelp();
    });
    function showHelp() {
        let dialog = document.querySelector('#install-help');
        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.id = 'install-help';
            dialog.className = 'install-help';
            dialog.setAttribute('aria-labelledby', 'install-help-title');
            dialog.innerHTML = '<img src="/icons/icon-192.png" alt="" width="64" height="64"><h2 id="install-help-title">Escopo Fácil na sua tela inicial</h2><p></p><form method="dialog"><button>Entendi</button></form>';
            document.body.append(dialog);
        }
        const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        dialog.querySelector('p').textContent = ios
            ? 'Abra o menu Compartilhar do navegador e escolha “Adicionar à Tela de Início”. Se a opção não aparecer, abra este site no Safari. O atalho usa a logo do Escopo Fácil e precisa de internet.'
            : 'No menu do navegador, procure “Instalar aplicativo” ou “Adicionar à tela inicial”. No computador, a opção também pode aparecer na barra de endereço. Se não estiver disponível, crie um atalho pelo menu do navegador. É necessário estar conectado à internet.';
        dialog.showModal();
    }
})();
