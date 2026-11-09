(() => {
    'use strict';
    const key = 'escopo-theme';
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = null;
    try { preference = localStorage.getItem(key); } catch { /* Storage may be unavailable. */ }
    if (!['light', 'dark'].includes(preference)) preference = null;

    function updateCharts(theme) {
        if (!window.Chart) return;
        const dark = theme === 'dark';
        const text = dark ? '#b5bfd0' : '#666';
        const accent = dark ? '#ff9cbc' : '#8b0836';
        Chart.defaults.color = text;
        Object.values(Chart.instances).forEach(chart => {
            chart.options.color = text;
            if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = text;
            Object.values(chart.options.scales || {}).forEach(scale => {
                if (scale.ticks) scale.ticks.color = text;
                if (scale.grid) scale.grid.color = dark ? '#404859' : '#e2e8f0';
            });
            chart.data.datasets.forEach(dataset => {
                if (chart.config.type === 'line') {
                    dataset.borderColor = dataset.pointBackgroundColor = accent;
                    dataset.backgroundColor = dark ? 'rgba(255,156,188,.08)' : 'rgba(139,8,54,.05)';
                } else if (chart.config.type === 'doughnut') {
                    dataset.backgroundColor = [accent, dark ? '#8795ae' : '#64748b', '#10b981'];
                }
            });
            chart.update('none');
        });
    }

    function apply(theme) {
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
        updateCharts(theme);
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.setAttribute('aria-checked', String(theme === 'dark'));
            button.title = theme === 'dark' ? 'Ativar modo claro' : 'Ativar modo escuro';
        });
        document.dispatchEvent(new CustomEvent('escopo:theme-change', { detail: { theme } }));
    }
    apply(preference || (media.matches ? 'dark' : 'light'));
    window.addEventListener('load', () => updateCharts(document.documentElement.dataset.theme));
    document.addEventListener('DOMContentLoaded', () => {
        apply(document.documentElement.dataset.theme);
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                preference = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem(key, preference); } catch { /* Theme still works in memory. */ }
                apply(preference);
            });
        });
    });
    media.addEventListener('change', () => {
        if (!preference) apply(media.matches ? 'dark' : 'light');
    });
    window.addEventListener('storage', event => {
        if (event.key !== key) return;
        preference = ['light', 'dark'].includes(event.newValue) ? event.newValue : null;
        apply(preference || (media.matches ? 'dark' : 'light'));
    });
})();
