<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200']]);
        $search = trim($data['search'] ?? '');
        $users = User::with('plan')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->latest('id')->paginate(25)->withQueryString();
        return $request->expectsJson() ? response()->json($users) : view('admin.subscribers', ['users' => $users, 'plans' => Plan::whereIn('slug', ['free', 'pro', 'equipe'])->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(['plan_id' => ['required', Rule::exists('plans', 'id')],
            'expires_at' => ['nullable', 'date', 'after:now']]);
        $plan = Plan::findOrFail($data['plan_id']);
        abort_unless(in_array($plan->slug, ['free', 'pro', 'equipe'], true), 422);
        if ($plan->slug !== 'free' && empty($data['expires_at'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['expires_at' => 'Informe a validade do plano pago.']);
        }
        DB::transaction(function () use ($user, $plan, $data) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            Subscription::where('user_id', $locked->id)->where('status', 'active')->update(['status' => 'replaced']);
            if ($plan->slug !== 'free') Subscription::create([
                'user_id' => $locked->id, 'plan_id' => $plan->id, 'status' => 'active', 'expires_at' => $data['expires_at'],
            ]);
            $locked->update(['plan_id' => $plan->id]);
        });
        return $request->expectsJson() ? response()->json(['message' => 'Assinatura atualizada.']) : back()->with('success', 'Assinatura atualizada.');
    }
}
