<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Support\AuthModal;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return AuthModal::view($request, 'register');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'job_role' => ['required', Rule::in(['dev-frontend', 'dev-backend', 'dev-fullstack', 'ui-ux-designer', 'product-owner', 'scrum-master', 'tech-lead', 'qa-testes'])],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
            'terms_version' => ['required', 'string', 'max:32', Rule::in([config('legal.version')])],
            'privacy_notice_version' => ['required', 'string', 'max:32', Rule::in([config('legal.version')])],
        ], [
            'terms_version.required' => 'Atualize a página para consultar a versão atual dos Termos de Uso.',
            'terms_version.in' => 'Os Termos de Uso foram atualizados. Atualize a página e consulte a nova versão antes de continuar.',
            'privacy_notice_version.required' => 'Atualize a página para consultar o Aviso de Privacidade.',
            'privacy_notice_version.in' => 'O Aviso de Privacidade foi atualizado. Atualize a página e consulte a nova versão antes de continuar.',
        ]);

        $freePlan = Plan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Plano Free', 'price_monthly' => 0, 'price_yearly' => 0,
            'limits' => ['projects' => -1, 'members' => -1],
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'job_role' => $validated['job_role'],
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.version'),
            'privacy_notice_version' => config('legal.version'),
            'plan_id' => $freePlan?->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        // The model sends the branded verification notification through Laravel.
        event(new Registered($user));

        return $request->expectsJson()
            ? AuthModal::json($request, 'verify-email', 'Conta criada! Confirme seu e-mail para continuar.')
            : redirect()->route('verification.notice');
    }
}
