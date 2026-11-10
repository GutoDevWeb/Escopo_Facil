<?php

namespace App\Services;

use App\Models\{Project, User};
use App\Notifications\ProjectActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\{DB, Log, Notification};
use Illuminate\Support\Str;

class ProjectActivity
{
    public function participants(Project $project)
    {
        return $project->members()->get()->push($project->owner)->unique('id');
    }

    public function publish(Project $project, User $actor, string $kind, string $body, ?User $affected = null, ?string $personal = null, bool $email = false): void
    {
        $recipients = $this->participants($project);
        if ($affected) $recipients->push($affected);
        foreach ($recipients->unique('id') as $user) {
            if ($user->id === $actor->id) continue;
            $this->store($user, $project, [
                'kind' => $kind, 'title' => 'Projeto: '.$project->title,
                'body' => $user->id === $affected?->id && $personal ? $personal : $body,
                'actor_id' => $actor->id, 'actor_name' => $actor->name,
            ], $email && $user->id === $affected?->id);
        }
    }

    public function store(User $user, Project $project, array $data, bool $email = false, ?string $key = null): void
    {
        $id = (string) Str::uuid();
        $values = [
            'id' => $id, 'type' => ProjectActivityNotification::class,
            'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id,
            'project_id' => $project->id, 'data' => json_encode($data + ['project_id' => $project->id, 'project_title' => $project->title], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'dedup_key' => $key, 'mail_pending' => $email,
            'created_at' => now(), 'updated_at' => now(),
        ];
        // A unique deadline key also prevents concurrent cron/browser checks from duplicating alerts.
        if ($key) {
            if (!DB::table('notifications')->insertOrIgnore($values)) return;
        } else DB::table('notifications')->insert($values);
        if ($email) DB::afterCommit(fn () => $this->deliver($id));
    }

    public function markProjectSeen(User $user, Project $project): void
    {
        $user->notifications()->where('project_id', $project->id)->whereNull('project_seen_at')->update(['project_seen_at' => now()]);
    }

    public function unreadProjects(User $user): array
    {
        return $user->notifications()->whereNull('project_seen_at')->whereNotNull('project_id')
            ->whereIn('project_id', Project::visibleTo($user)->select('projects.id'))
            ->distinct()->pluck('project_id')->all();
    }

    public function retryMail(): void
    {
        DatabaseNotification::where('mail_pending', true)
            ->where(fn ($q) => $q->whereNull('mail_retry_at')->orWhere('mail_retry_at', '<=', now()))
            ->orderBy('created_at')->limit(50)->pluck('id')->each(fn ($id) => $this->deliver($id));
    }

    public function deliver(string $id): void
    {
        // Claim a delivery before contacting SMTP. Failed mail never cancels membership or loses the in-app notice.
        $claimed = DatabaseNotification::whereKey($id)->where('mail_pending', true)
            ->where(fn ($q) => $q->whereNull('mail_retry_at')->orWhere('mail_retry_at', '<=', now()))
            ->update(['mail_retry_at' => now()->addMinutes(15)]);
        if (!$claimed) return;
        $row = DatabaseNotification::find($id);
        $user = $row?->notifiable;
        if (!$user || !$user->hasVerifiedEmail()) {
            $row?->update(['mail_pending' => false]);
            return;
        }
        if (($row->data['kind'] ?? null) === 'member_added') {
            $project = Project::visibleTo($user)->find($row->project_id);
            if (!$project) {
                $row->update(['mail_pending' => false]);
                return;
            }
        }
        try {
            Notification::sendNow($user, new ProjectActivityNotification($row->data), ['mail']);
            $row->update(['mail_pending' => false, 'mail_sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('project_notification_mail_failed', ['notification_id' => $id, 'exception' => $e::class]);
        }
    }
}
