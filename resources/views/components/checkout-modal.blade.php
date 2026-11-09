@props(['initialPeriod' => 'yearly', 'initialOpen' => false, 'initialPlan' => 'pro'])
@php
    $checkoutPeriod = in_array($initialPeriod, ['monthly', 'yearly'], true) ? $initialPeriod : 'yearly';
@endphp
<dialog id="checkout-modal" class="login-modal checkout-modal" aria-labelledby="payment-title" data-initial-period="{{ $checkoutPeriod }}" data-initial-plan="{{ $initialPlan }}" data-initial-open="{{ $initialOpen ? 'true' : 'false' }}">
    <button class="login-modal-close" type="button" data-close-checkout aria-label="Fechar pagamento">&times;</button>
    <div class="modal-scroll" data-modal-scroll>
    <div id="checkout" class="login-modal-content" data-endpoint="{{ route('subscription.process') }}" data-options-url="{{ route('subscription.options') }}" data-status-base="{{ url('/payments') }}" data-home="{{ url('/') }}" data-period="{{ $checkoutPeriod }}">
        <img class="login-modal-logo" src="{{ asset('assets/landing/logo-original.svg') }}" alt="Escopo Fácil" width="250" height="72">

        @if(app()->environment(['local', 'testing']))
        <span class="checkout-simulation-badge"><span aria-hidden="true"></span>Simulação · sem cobrança real</span>
        @endif

        <aside class="checkout-order" aria-label="Resumo do plano">
            <div class="checkout-order-top">
                <span class="checkout-plan-name" data-checkout-plan-name>Escopo <strong>{{ $initialPlan === 'equipe' ? 'Equipe' : 'Pro' }}</strong></span>
                <div class="checkout-periods" role="group" aria-label="Período do plano">
                    <button type="button" data-checkout-period="monthly" aria-pressed="{{ $checkoutPeriod === 'monthly' ? 'true' : 'false' }}">Mensal</button>
                    <button type="button" data-checkout-period="yearly" aria-pressed="{{ $checkoutPeriod === 'yearly' ? 'true' : 'false' }}">Anual</button>
                </div>
            </div>
            <div class="checkout-order-total"><span>Total do plano</span><strong data-checkout-total>R$ {{ $checkoutPeriod === 'yearly' ? '238,80' : '27,90' }}</strong></div>
            <p class="checkout-order-term" data-checkout-term>{{ $checkoutPeriod === 'yearly' ? '12 meses de acesso. Pagamento único.' : '1 mês de acesso. Pagamento único.' }}</p>
            <p class="checkout-renewal">Sem renovação automática.</p>
            <p class="checkout-account" data-checkout-email @if(! auth()->check()) hidden @endif>{{ auth()->user()?->email }}</p>
        </aside>

        <div id="payment-feedback" class="checkout-feedback" role="alert" tabindex="-1" hidden></div>

        <section id="payment-intro" aria-labelledby="payment-title">
            <h2 id="payment-title" tabindex="-1">Seu próximo passo é Pro</h2>
            <p class="checkout-description">{{ app()->environment('production') ? 'Conheça os recursos do plano e fale com nossa equipe para saber sobre a disponibilidade.' : 'Mais controle para seus projetos. Gere o QR Code PIX e conclua a simulação para liberar seu plano.' }}</p>
            <button type="button" id="generate-pix" class="primary login-modal-submit" @if(app()->environment('production')) hidden @endif>Gerar QR Code PIX</button>
            <button type="button" id="payment-login" class="primary login-modal-submit" hidden>Entrar para continuar</button>
            <button type="button" id="payment-verify" class="primary login-modal-submit" hidden>Confirmar meu e-mail</button>
            <button type="button" id="payment-check-verification" class="secondary" hidden>Já confirmei meu e-mail</button>
            <p class="checkout-note">{{ app()->environment('production') ? 'A contratação online será disponibilizada após a ativação do serviço de pagamentos.' : (config('payments.driver') === 'local' ? 'O QR Code é demonstrativo e não é aceito em aplicativos de banco. Use “Simular pagamento” para testar.' : 'Pagamento em ambiente de teste. Utilize a simulação, sem transferir dinheiro.') }}</p>
        </section>

        <section id="payment-pending" aria-labelledby="pix-title" hidden>
            <h2 id="pix-title" tabindex="-1">Seu QR Code está pronto</h2>
            <p class="checkout-description">Conclua a simulação para começar a usar seu Pro.</p>
            <div class="checkout-qr-wrap"><canvas id="pix-qr" role="img" aria-label="QR Code de pagamento em ambiente de simulação"></canvas></div>
            <p class="checkout-countdown">Válido por <strong id="pix-countdown">--:--</strong></p>
            <label class="checkout-copy-label" for="pix-code">Código PIX copia e cola · teste</label>
            <div class="checkout-copy-row"><input id="pix-code" class="checkout-copy-code" type="text" readonly spellcheck="false" autocomplete="off"><button type="button" id="copy-pix" class="secondary">Copiar código</button></div>
            <p class="checkout-payment-status" role="status"><span aria-hidden="true"></span>Aguardando confirmação do pagamento</p>
            <button type="button" id="simulate-pix" class="primary login-modal-submit">Simular pagamento</button>
            <button type="button" id="check-pix" class="secondary">Consultar confirmação</button>
        </section>

        <section id="payment-expired" class="checkout-complete" aria-labelledby="expired-title" hidden>
            <span class="checkout-state-icon checkout-expired-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <h2 id="expired-title" tabindex="-1">Este QR Code expirou</h2>
            <p class="checkout-description">Gere um novo código para continuar sua simulação.</p>
            <button type="button" id="retry-pix" class="primary login-modal-submit">Gerar novo QR Code</button>
        </section>

        <section id="payment-paid" class="checkout-complete" aria-labelledby="paid-title" hidden>
            <span class="checkout-state-icon checkout-paid-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 12 4 4 8-8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <h2 id="paid-title" tabindex="-1">Seu Pro está liberado!</h2>
            <p class="checkout-description">Pagamento simulado confirmado. Agora é hora de colocar seus projetos em movimento.</p>
            <a href="{{ route('dashboard') }}" class="primary login-modal-submit" data-system-entry>Acessar o sistema</a>
        </section>

        <section id="payment-loading" class="checkout-loading" role="status" aria-live="polite" hidden><span class="checkout-spinner" aria-hidden="true"></span><p>Preparando seu pagamento…</p></section>
        <noscript><p class="checkout-note">Ative o JavaScript para gerar o QR Code e acompanhar o pagamento.</p></noscript>
    </div>
    </div>
</dialog>
