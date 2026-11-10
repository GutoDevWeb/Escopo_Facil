@props(['startUrl'])
<section class="features-section section" id="features" aria-label="Funcionalidades do Escopo Fácil">
    <div class="feature-row">
        <div class="feature-visual"><img src="{{ asset('assets/landing/feature-project.jpg') }}" alt="Gerenciamento de um projeto com título, data e membros da equipe" width="609" height="1024" loading="lazy"></div>
        <div class="feature-copy">
            <p class="eyebrow">Gestão de projetos</p>
            <h2>Mantenha cada projeto andando sem bagunça</h2>
            <p class="feature-description"><strong>Crie, organize e acompanhe tarefas em um só lugar. Com membros, prazos e andamento visíveis, sua equipe sabe o que fazer e o projeto não se perde no caminho.</strong></p>
            <a class="button" data-login-trigger aria-haspopup="dialog" aria-controls="login-modal" href="{{ route('login') }}">Começar agora</a>
            <ul class="feature-tags"><li><span aria-hidden="true">☷</span> Tarefas</li><li><span aria-hidden="true">◷</span> Membros</li><li><span aria-hidden="true">☰</span> Projetos</li><li><span aria-hidden="true">▤</span> Relatórios</li></ul>
        </div>
    </div>
    <div class="feature-row feature-row-reverse">
        <div class="feature-copy">
            <p class="eyebrow">Acompanhamento de tarefas</p>
            <h2>Controle tarefas,<br>prazos e prioridades</h2>
            <p class="feature-description">Acompanhe o que está em andamento, o que já foi concluído e o que ainda precisa de atenção. Tudo fica visível para a equipe seguir no mesmo ritmo.</p>
            <a class="button" data-login-trigger aria-haspopup="dialog" aria-controls="login-modal" href="{{ route('login') }}">Organizar tarefas</a>
            <ul class="feature-tags"><li><span aria-hidden="true">⚑</span> Prioridades</li><li><span aria-hidden="true">◷</span> Prazos</li><li><span aria-hidden="true">↗</span> Andamento</li><li><span aria-hidden="true">☷</span> Tarefas</li></ul>
        </div>
        <div class="feature-visual"><picture>
            <source media="(max-width: 809px)" srcset="{{ asset('assets/landing/dashboard.jpg') }}" width="1425" height="1024">
            <img src="{{ asset('assets/landing/feature-tasks.png') }}" alt="Quadro de tarefas com prioridades, prazos e status de andamento" width="1440" height="1024" loading="lazy">
        </picture></div>
    </div>
</section>
<section class="resources-section section" id="beneficios" aria-labelledby="resources-title">
    <p class="eyebrow">Recursos</p>
    <h2 id="resources-title">Feito para organizar projetos,<br>sem complicar a equipe</h2>
    <div class="resource-cards">
        <article class="resource-card">
            <h3>Simples, visual e adaptado<br>ao fluxo do seu projeto</h3>
            <div class="resource-art"><img src="{{ asset('assets/landing/benefit-customize.svg') }}" alt="Ilustração de opções de cores e apresentação da interface" width="466" height="178" loading="lazy"></div>
            <p><strong>Organize do seu jeito.</strong> Crie projetos, defina membros e acompanhe tarefas com uma interface clara, pensada para equipes que precisam sair da bagunça e entregar melhor.</p>
        </article>
        <article class="resource-card">
            <h3>Tudo conectado para sua equipe<br>trabalhar no mesmo lugar</h3>
            <div class="resource-art collaboration-art" aria-hidden="true">
                @for($row = 0; $row < 2; $row++)
                    <div class="collaboration-track">
                        @foreach([0, 1, 2, 3, 4, 5, 6, 7, 8, 0, 1, 2, 3, 4, 5, 6, 7, 8] as $icon)
                            <img src="{{ asset('assets/landing/benefit-icon-' . $icon . '.svg') }}" alt="" width="76" height="76">
                        @endforeach
                    </div>
                @endfor
            </div>
            <p><strong>Colaboração sem ruído.</strong> Centralize tarefas, responsáveis e andamento em um único painel. Menos mensagens perdidas, mais clareza sobre o que precisa ser feito.</p>
        </article>
    </div>
    <div class="resource-highlights">
        <article><span class="resource-icon" aria-hidden="true">♧</span><h3>Colabore em tempo real</h3><p>Mantenha todos alinhados com tarefas visíveis, membros definidos em cada projeto.</p></article>
        <article><span class="resource-icon" aria-hidden="true">⚑</span><h3>Controle prioridades</h3><p>Classifique tarefas por prioridade, prazo e status para saber o que exige atenção primeiro.</p></article>
        <article><span class="resource-icon" aria-hidden="true">▦</span><h3>Veja tudo do seu jeito</h3><p>Acompanhe projetos, tarefas e membros em telas simples, com foco no que realmente importa.</p></article>
    </div>
</section>
