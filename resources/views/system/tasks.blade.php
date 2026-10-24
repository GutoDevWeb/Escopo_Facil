@extends('layouts.system')
@section('content')
<section class="page-content" data-projeto-id="<?= $projetoId ?>">

    <div class="page-header">
        <div class="page-header-left">
            <?php if ($projeto): ?>
                <a href="{{ url('/system') }}/index.php?page=projetos" class="back-link">
                    <img src="{{ url('/system') }}/assets/icon/arrow.svg" alt="voltar">
                </a>
            <?php endif; ?>
            <h1 class="headline">
                <?= $projeto ? htmlspecialchars($projeto['titulo']) : 'Tarefas' ?>
            </h1>
        </div>


        <div class="header-actions">
            @include('system.notification-bell')

            <div class="member-profile user-menu-trigger" style="padding:6px 8px;border-radius:8px">
                <?= \App\Support\SystemAvatar::renderAvatar($avatarStr, $usuario['nome'] ?? 'U', 'avatar') ?>

                <div class="member-info">
                    <p class="member-name"><?= htmlspecialchars($usuario['nome'] ?? 'Usuario') ?></p>
                    <p class="member-category"><?= htmlspecialchars($usuario['cargo'] ?? 'Membro') ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$projetoId): ?>
        <div class="no-project-message">
            <p>Nenhum projeto selecionado.</p>
            <p>Acesse a página <strong>Projetos</strong> e clique em <strong>"Ver tarefas"</strong> para começar.</p>
            <a href="{{ url('/system') }}/index.php?page=projetos" class="no-project-btn">Ir para Projetos</a>
        </div>
    <?php elseif (!$projeto): ?>
        <div class="no-project-message">
            <p>Projeto nao encontrado.</p>
        </div>
    <?php elseif (!$isMembro): ?>
        <div class="no-project-message">
            <p>Voce nao tem acesso as tarefas deste projeto.</p>
        </div>
    <?php else: ?>
        @include('system.plan-overview', ['projectAccess' => $access])
        <nav class="system-project-links">
            @if(in_array('reports', $access['features']))<a href="{{ route('projects.report', $project) }}">Relatório do projeto</a>@else<span class="feature-locked-link" title="Disponível a partir do Pro">@include('system.lock-icon') Relatório do projeto · Pro</span>@endif
            @if(in_array('history', $access['features']))<a href="{{ route('projects.history', $project) }}">Histórico de tarefas</a>@else<span class="feature-locked-link" title="Disponível no Equipe">@include('system.lock-icon') Histórico de tarefas · Equipe</span>@endif
        </nav>
        <div class="task-search-container">
            <div class="search-input-wrapper">
                <img src="{{ url('/system') }}/assets/icon/search.svg" alt="lupa de pesquisa">
                <input
                    type="text"
                    placeholder="Busque por prioridade, data ou titulo da tarefa"
                >
            </div>


            <button class="btn-new-task-main">
                <img src="{{ url('/system') }}/assets/icon/plus.svg" alt="+">
                Nova tarefa
            </button>
        </div>

        <div class="kanban">
            <?php
                $colunas = [
                    1 => ['titulo' => 'A Fazer', 'icone' => 'a-fazer.svg', 'alt' => 'A Fazer'],
                    2 => ['titulo' => 'Em Andamento', 'icone' => 'loading.svg', 'alt' => 'Em Andamento'],
                    3 => ['titulo' => 'Concluido', 'icone' => 'concluido.svg', 'alt' => 'Concluido'],
                ];
            ?>

            <?php foreach ($colunas as $status => $coluna): ?>
                <div class="kanban-column" id="col-<?= $status ?>" data-status="<?= $status ?>">
                    <div class="task-title">
                        <h3 class="title"><?= $coluna['titulo'] ?></h3>
                        <img src="{{ url('/system') }}/assets/icon/<?= $coluna['icone'] ?>" alt="<?= $coluna['alt'] ?>">
                    </div>

                    <?php foreach ($tarefasPorStatus($tarefas, $status) as $tarefa):
                        $prioridadeId = (int) $tarefa['prioridade'];
                        $prioridade = $prioridades[$prioridadeId] ?? $prioridades[1];
                    ?>
                        <div
                            class="task-card"
                            draggable="true"
                            data-id="<?= (int) $tarefa['id'] ?>"
                            data-titulo="<?= htmlspecialchars($tarefa['titulo']) ?>"
                            data-descricao="<?= htmlspecialchars($tarefa['descricao'] ?? '') ?>"
                            data-prioridade="<?= $prioridadeId ?>"
                            data-status="<?= (int) $tarefa['status'] ?>"
                            data-prazo="<?= htmlspecialchars($tarefa['prazo'] ?? '') ?>"
                            data-criado-por-nome="<?= htmlspecialchars($tarefa['criado_por_nome'] ?? '') ?>"
                            data-criado-por-avatar="<?= htmlspecialchars($tarefa['criado_por_avatar'] ?? '') ?>"
                            data-checklist-total="<?= (int)($tarefa['checklist_total'] ?? 0) ?>"
                            data-checklist-done="<?= (int)($tarefa['checklist_done'] ?? 0) ?>"
                        >
                            <div class="task-description">
                                <h3><?= htmlspecialchars($tarefa['titulo']) ?></h3>

                                <div class="background-description">
                                    <p><?= nl2br(htmlspecialchars($tarefa['descricao'] ?? '')) ?></p>
                                </div>

                                <div class="task-status">
                                    <div class="priority-tag <?= $prioridade['classe'] ?>">
                                        <p>Prioridade: <?= $prioridade['texto'] ?></p>
                                        <img src="{{ url('/system') }}/assets/icon/<?= $prioridade['icone'] ?>" alt="prioridade">
                                    </div>

                                    <div class="datetime-priority">
                                        <p>Prazo:</p>
                                        <img src="{{ url('/system') }}/assets/icon/calendar.svg" alt="calendario">
                                        <span class="deadline-text"><?= $formatarPrazo($tarefa['prazo'] ?? null) ?></span>
                                    </div>
                                </div>

                                <?php
                                    $clTotal = (int)($tarefa['checklist_total'] ?? 0);
                                    $clDone  = (int)($tarefa['checklist_done'] ?? 0);
                                ?>
                                <div class="task-btn">
                                    <div class="task-btn-left">
                                        <svg class="card-open-hint" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
                                        <div class="checklist-badge" data-total="<?= $clTotal ?>" data-done="<?= $clDone ?>">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><polyline points="9 12 11 14 15 10"/></svg>
                                            <span class="checklist-badge-count"><?= $clDone ?>/<?= $clTotal ?></span>
                                        </div>
                                    </div>
                                    <?= \App\Support\SystemAvatar::renderAvatar($tarefa['criado_por_avatar'] ?? '', $tarefa['criado_por_nome'] ?? 'U', 'avatar-card') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button class="btn-add-card" data-status="<?= $status ?>">
                        <span class="btn-add-card-left">
                            <img src="{{ url('/system') }}/assets/icon/plus.svg" alt="+">
                            Adicionar cartão
                        </span>
                        <img src="{{ url('/system') }}/assets/icon/tarefas.svg" alt="" class="btn-add-card-icon-right">
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="no-tasks-message" style="display: none; text-align: center; padding: 20px; color: #666;">
            <p>Nenhuma tarefa encontrada com este termo.</p>
        </div>

        @include('system.modals.task')
    <?php endif; ?>
</section>

<script>
    window.projetoId = <?= json_encode($projetoId) ?>;
    window.tarefasData = <?= json_encode($tarefas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>


@endsection
