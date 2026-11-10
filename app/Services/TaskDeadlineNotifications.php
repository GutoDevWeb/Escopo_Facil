<?php

namespace App\Services;

use App\Models\{Project, Task, User};
use Carbon\CarbonImmutable;

class TaskDeadlineNotifications
{
    public function send(?User $onlyUser = null, ?Project $onlyProject = null): void
    {
        $now = CarbonImmutable::now('America/Sao_Paulo');
        // A date-only task expires at the end of that day in Brazil.
        $query = Task::where('status', '!=', 'done')->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->toDateString(), $now->addDay()->toDateString()])
            ->whereHas('project', fn ($q) => $q->where('archived', false));
        if ($onlyUser) $query->whereIn('project_id', Project::visibleTo($onlyUser)->select('projects.id'));
        if ($onlyProject) $query->where('project_id', $onlyProject->id);
        $activity = app(ProjectActivity::class);
        $query->with('project.owner')->chunkById(100, function ($tasks) use ($now, $onlyUser, $activity) {
            foreach ($tasks as $task) {
                $deadline = CarbonImmutable::parse($task->due_date->format('Y-m-d'), 'America/Sao_Paulo')->endOfDay();
                if ($deadline->lessThanOrEqualTo($now) || $deadline->greaterThan($now->addHours(24))) continue;
                $project = $task->project;
                $users = $onlyUser ? collect([$onlyUser]) : $activity->participants($project);
                foreach ($users as $user) $activity->store($user, $project, [
                    'kind' => 'deadline', 'title' => 'Prazo próximo: '.$project->title,
                    'body' => 'A tarefa "'.$task->title.'", do projeto "'.$project->title.'", vence em até 24 horas ('.$deadline->format('d/m/Y').' às 23h59, horário de Brasília).',
                    'task_id' => $task->id,
                ], false, 'deadline:'.$task->id.':'.$deadline->toDateString().':'.$user->id);
            }
        });
    }
}
