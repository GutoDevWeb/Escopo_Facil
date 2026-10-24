<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Lista todos os planos disponíveis.
     */
    public function index()
    {
        $plans = Plan::all();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Mostra o formulário para criar um novo plano.
     */
    public function create()
    {
        return view('admin.plans.create');
    }

    /**
     * Guarda o novo plano na base de dados.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'limits' => 'sometimes|array:projects,members',
            'limits.projects' => 'sometimes|integer|min:-1',
            'limits.members' => 'sometimes|integer|min:-1',
        ]);

        Plan::create($request->only(['name', 'slug', 'description', 'price_monthly', 'price_yearly', 'limits']));

        return redirect()->route('admin.plans.index')->with('success', 'Plano criado com sucesso!');
    }

    /**
     * Mostra o formulário para editar um plano existente.
     */
    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Atualiza o plano na base de dados.
     */
    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'limits' => 'sometimes|array:projects,members',
            'limits.projects' => 'sometimes|integer|min:-1',
            'limits.members' => 'sometimes|integer|min:-1',
        ]);

        if ($request->slug !== $plan->slug && (\App\Models\User::where('plan_id', $plan->id)->exists() || \App\Models\Subscription::where('plan_id', $plan->id)->exists())) {
            throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'Não altere o identificador de um plano já utilizado.']);
        }
        $plan->update($request->only(['name', 'slug', 'description', 'price_monthly', 'price_yearly', 'limits']));

        return redirect()->route('admin.plans.index')->with('success', 'Plano atualizado com sucesso!');
    }

    /**
     * Remove o plano da base de dados.
     */
    public function destroy(Plan $plan)
    {
        abort_if(\App\Models\User::where('plan_id', $plan->id)->exists() || \App\Models\Subscription::where('plan_id', $plan->id)->exists()
            || \App\Models\PixPayment::where('plan_id', $plan->id)->exists(), 409, 'Plano em uso. Não é possível excluí-lo.');
        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Plano removido com sucesso!');
    }
}
