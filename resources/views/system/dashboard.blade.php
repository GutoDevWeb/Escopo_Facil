<div class="page-header">
    <h1 class="headline">Olá, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?> 👋</h1>
    <div class="header-actions">
        @include('system.notification-bell')
        <div class="member-profile user-menu-trigger" style="padding:6px 8px;border-radius:8px">
            <?= \App\Support\SystemAvatar::renderAvatar($usuario['avatar'], $usuario['nome'], 'avatar') ?>
            <div class="member-info">
                <p class="member-name"><?= htmlspecialchars($usuario['nome']) ?></p>
                <p class="member-category"><?= htmlspecialchars($usuario['role']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-container">

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Projetos Ativos</h3>
            <p class="number"><?= $totalProjetos ?></p>
        </div>
        <div class="stat-card warning">
            <h3>Tarefas Pendentes</h3>
            <p class="number"><?= $tarefasPendentes ?></p>
        </div>
        <div class="stat-card success">
            <h3>Conclusão do Escopo</h3>
            <p class="number"><?= $progresso ?>%</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $progresso ?>%;"></div>
            </div>
        </div>
    </div>

    <div class="dashboard-bottom">

        <div class="stat-card dashboard-chart-card">
            <h3>Distribuição de Tarefas</h3>
            <div class="chart-wrapper">
                <canvas id="tasksChart"></canvas>
            </div>
        </div>

        <div class="dashboard-right">

            <div class="stat-card dashboard-activity-card">
                <h3>Atividades Recentes</h3>
                <ul class="activity-list">
                    <?php foreach ($recentes as $item):
                        $s = (int) $item['status'];
                    ?>
                        <li class="activity-item">
                            <span class="activity-title"><?= htmlspecialchars($item['titulo']) ?></span>
                            <span class="status-tag <?= $statusClass[$s] ?? '' ?>"><?= $statusLabel[$s] ?? '-' ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($recentes)): ?>
                        <li class="activity-empty">Nenhuma tarefa ainda.</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="stat-card dashboard-line-card">
                <h3>Ritmo de Trabalho</h3>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
window.addEventListener('load', function () {
    const darkTheme = document.documentElement.dataset.theme === 'dark';
    const chartAccent = darkTheme ? '#ff9cbc' : '#8b0836';
    const cores = [chartAccent, darkTheme ? '#8795ae' : '#64748b', '#10b981'];

    // GRÁFICO DE ROSCA (Status)
    const ctxStatus = document.getElementById('tasksChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Pendente', 'Em Andamento', 'Concluído'],
                datasets: [{
                    data: [<?= $tarefasPendentes ?>, <?= $tarefasFazendo ?>, <?= $tarefasConcluidas ?>],
                    backgroundColor: cores,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, font: { size: 13 } }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // GRÁFICO DE LINHA (Ritmo) 
    const ctxLine = document.getElementById('lineChart');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?= $labelsDias ?>,
                datasets: [{
                    label: 'Concluídas',
                    data: <?= $performanceJson ?>,
                    borderColor: chartAccent,
                    backgroundColor: darkTheme ? 'rgba(255,156,188,.08)' : 'rgba(139,8,54,.05)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: chartAccent
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { display: false }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
