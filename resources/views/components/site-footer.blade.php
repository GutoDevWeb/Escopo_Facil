<footer class="site-footer">
    <div class="site-install"><button type="button" data-app-install>Adicionar Escopo Fácil à tela inicial ↗</button></div>
    <div class="site-footer-inner">
        <div class="site-footer-columns">
            <nav aria-label="Website"><h2>Website</h2><a href="{{ url('/') }}">Home</a><a href="#features">Funcionalidades</a><a href="#beneficios">Benefícios</a><a href="#precos">Planos</a><a href="#contato">Contato</a></nav>
            <nav aria-label="Recursos"><h2>Recursos</h2><a href="#features">Gestão de projetos</a><a href="#features">Tarefas e prazos</a><a href="#beneficios">Colaboração em equipe</a><a href="#acesso">Acesso pelo navegador</a></nav>
            <nav aria-label="Sua conta"><h2>Sua conta</h2><a href="{{ route('login') }}" data-login-trigger aria-haspopup="dialog" aria-controls="login-modal">Entre</a><a href="{{ route('register') }}">Crie sua conta</a><a href="{{ route('password.request') }}" data-auth-screen="forgot-password">Recuperar senha</a></nav>
            <nav aria-label="Suporte">
                <h2>Suporte</h2>
                @if(config('legal.whatsapp_url'))
                <a class="footer-support-detail" href="{{ config('legal.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"><x-whatsapp-icon /><span>{{ config('legal.whatsapp_label') }}</span></a>
                @elseif(! app()->environment('production'))
                <span class="footer-support-detail">(11) 00000-0000<small>WhatsApp demonstrativo</small></span>
                @endif
                <a class="footer-support-detail" href="mailto:{{ config('legal.support_email') }}"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="m3 6 9 7 9-7" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg><span>{{ config('legal.support_email') }}</span></a>
                <span class="footer-support-detail"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span>Atendimento das 8h às 18h</span></span>
                <a href="#contato">Falar com a equipe</a>
            </nav>
            <nav aria-label="Legal"><h2>Legal</h2><a href="{{ route('legal.terms') }}" data-legal-document>Termos de Uso</a><a href="{{ route('legal.privacy') }}" data-legal-document>Aviso de Privacidade</a><a href="{{ route('legal.privacy') }}" data-legal-document>Privacidade e dados</a></nav>
        </div>
        <div class="site-footer-bottom">
            <div class="site-footer-identity">
                <a href="{{ url('/') }}" aria-label="Escopo Fácil — início"><img class="site-footer-logo" src="{{ asset('assets/landing/logo-original.svg') }}" alt="Escopo Fácil" width="185" height="53"></a>
                <p>© {{ date('Y') }} Escopo Fácil · Projeto acadêmico</p>
            </div>
            <a class="lgpd-badge" href="{{ route('legal.privacy') }}" data-legal-document aria-label="LGPD — consultar o Aviso de Privacidade"><img src="{{ asset('assets/landing/lgpd-informativo.svg') }}" alt="LGPD — Privacidade e dados" width="182" height="56"></a>
        </div>
        <p class="site-footer-description">Organização simples para projetos, tarefas e equipes. Desenvolvido no Centro Universitário Senac, no curso de Tecnologia em Sistemas para Internet. Ambiente acadêmico de simulação, sem cobranças reais. Consulte os <a href="{{ route('legal.terms') }}" data-legal-document>Termos de Uso</a> e o <a href="{{ route('legal.privacy') }}" data-legal-document>Aviso de Privacidade</a>.</p>
        <p class="site-footer-credit">Criado por Natan Oliveira, Guilherme Augusto e Nicollas Ramos</p>
        <div class="footer-socials" role="group" aria-label="Redes sociais — demonstração, sem links">
            <span role="img" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.5" cy="6.5" r="1.1"/></svg></span>
            <span role="img" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 22v-9h3l.5-4H14V6.8c0-1.1.4-1.8 1.9-1.8H18V1.4a25 25 0 0 0-3-.2c-3 0-5 1.8-5 5.1V9H7v4h3v9h4Z"/></svg></span>
            <span role="img" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.3 7.5a2.2 2.2 0 1 0 0-4.4 2.2 2.2 0 0 0 0 4.4ZM3.5 9h3.6v12H3.5V9Zm6 0h3.4v1.6h.1A4 4 0 0 1 16.6 8c3.8 0 4.4 2.4 4.4 5.5V21h-3.6v-6.6c0-1.6 0-3-1.9-3s-2.3 1.4-2.3 2.9V21H9.5V9Z"/></svg></span>
            <span role="img" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.2 7.1a3 3 0 0 0-2.1-2.2C18.3 4.4 12 4.4 12 4.4s-6.3 0-8.1.5a3 3 0 0 0-2.1 2.2A31 31 0 0 0 1.3 12a31 31 0 0 0 .5 4.9 3 3 0 0 0 2.1 2.2c1.8.5 8.1.5 8.1.5s6.3 0 8.1-.5a3 3 0 0 0 2.1-2.2 31 31 0 0 0 .5-4.9 31 31 0 0 0-.5-4.9ZM9.8 15.4V8.6L15.5 12l-5.7 3.4Z"/></svg></span>
        </div>
    </div>
</footer>
