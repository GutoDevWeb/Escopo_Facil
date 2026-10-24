<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\{Controller, SystemController};
use App\Models\{User, Project, Task, Subscription, PixPayment};
use App\Services\PlanAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlanAccess $access)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:200']);
        $query = User::query()->latest('id');
        if ($filters['search'] ?? null) $query->where(fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')->orWhere('email', 'like', '%'.$filters['search'].'%'));
        $users = $query->paginate(20)->withQueryString();
        $sessions = config('session.driver') === 'database'
            ? DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->whereNotNull('user_id')->where('last_activity', '>=', now()->subMinutes(config('session.lifetime'))->timestamp)->get(['user_id', 'last_activity'])
            : collect();
        $online = $sessions->where('last_activity', '>=', now()->subMinutes(5)->timestamp)->pluck('user_id')->unique();
        $lastSeen = DB::table('usage_metrics')->selectRaw('user_id, MAX(last_seen_at) AS last_seen')->groupBy('user_id')->pluck('last_seen', 'user_id');
        $rows = $users->getCollection()->map(fn ($u) => ['user' => $u, 'plan' => $access->summary($u),
            'online' => $online->contains($u->id), 'session' => $sessions->contains('user_id', $u->id), 'last_seen' => $lastSeen[$u->id] ?? null]);
        $days = collect(range(13, 0))->map(fn ($n) => today()->subDays($n)->toDateString());
        $usage = DB::table('usage_metrics')->where('day', '>=', $days->first())->selectRaw('day, SUM(requests) AS requests, SUM(logins) AS logins')->groupBy('day')->get()->keyBy('day');
        $series = $days->map(fn ($d) => ['day' => substr($d, 8).'/'.substr($d, 5, 2), 'requests' => (int) ($usage[$d]->requests ?? 0), 'logins' => (int) ($usage[$d]->logins ?? 0)]);
        $metrics = ['users' => User::count(), 'verified' => User::whereNotNull('email_verified_at')->count(),
            'online' => $online->count(), 'sessions' => $sessions->count(),
            'projects' => Project::count(), 'tasks' => Task::count(), 'done' => Task::where('status', 'done')->count(),
            'logins' => (int) DB::table('usage_metrics')->sum('logins'), 'requests' => (int) DB::table('usage_metrics')->sum('requests'),
            'subscriptions' => Subscription::whereHas('plan', fn ($q) => $q->whereIn('slug', ['pro', 'equipe']))->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->where('expires_at', '>', now())->whereHas('plan', fn ($q) => $q->whereIn('slug', ['pro', 'equipe']))->count(),
            'purchases' => PixPayment::where('status', 'paid')->count(),
            'simulated_amount' => PixPayment::where('status', 'paid')->sum('amount') / 100];
        return view('admin.dashboard', app(SystemController::class)->common($request, 'admin') + compact('metrics', 'users', 'rows', 'series'));
    }
}
