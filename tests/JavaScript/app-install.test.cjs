const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup(standalone = false) {
    const listeners = {};
    const button = { hidden: false };
    const media = { matches: standalone, addEventListener: (name, callback) => { listeners['media:' + name] = callback; } };
    const on = (name, callback) => { listeners[name] = callback; };
    vm.runInNewContext(fs.readFileSync('public/assets/app-install.js', 'utf8'), {
        window: { matchMedia: () => media, addEventListener: on },
        navigator: { standalone: false },
        document: { addEventListener: on, querySelectorAll: () => [button] },
    });
    return { listeners, button, media };
}

test('o botão fica oculto quando aberto como aplicativo instalado', () => {
    const { listeners, button } = setup(true);
    listeners.DOMContentLoaded();
    assert.equal(button.hidden, true);
});

test('o prompt nativo só é solicitado após clicar e é consumido uma vez', async () => {
    const { listeners, button } = setup();
    let calls = 0, prevented = false;
    listeners.beforeinstallprompt({ preventDefault: () => { prevented = true; }, prompt: async () => { calls++; }, userChoice: Promise.resolve({ outcome: 'dismissed' }) });
    assert.equal(prevented, true);
    assert.equal(calls, 0);
    await listeners.click({ target: { closest: () => button } });
    assert.equal(calls, 1);
    listeners.appinstalled();
    assert.equal(button.hidden, true);
});

test('a mudança para janela instalada atualiza a visibilidade do botão', () => {
    const { listeners, button, media } = setup();
    listeners.DOMContentLoaded();
    assert.equal(button.hidden, false);
    media.matches = true;
    listeners['media:change']();
    assert.equal(button.hidden, true);
});

test('o manifesto aponta para ícones PNG existentes e abre o dashboard', () => {
    const manifest = JSON.parse(fs.readFileSync('public/manifest.webmanifest', 'utf8'));
    assert.equal(manifest.start_url, '/dashboard');
    assert.equal(manifest.display, 'standalone');
    for (const icon of manifest.icons) {
        const file = fs.readFileSync('public' + icon.src.split('?')[0]);
        assert.equal(file.subarray(1, 4).toString(), 'PNG');
        const size = Number(icon.sizes.split('x')[0]);
        assert.equal(file.readUInt32BE(16), size);
        assert.equal(file.readUInt32BE(20), size);
    }
});
