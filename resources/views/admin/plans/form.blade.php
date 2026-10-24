<div class="admin-form-grid">
    <label>Nome do plano<input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" required maxlength="255"></label>
    <label>Identificador<input type="text" name="slug" value="{{ old('slug', $plan->slug ?? '') }}" required maxlength="255"><small>Ex.: free, pro, equipe. Não altere identificadores de planos em uso.</small></label>
    <label>Preço mensal (R$)<input type="number" step="0.01" min="0" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly ?? '') }}" required></label>
    <label>Preço anual (R$)<input type="number" step="0.01" min="0" name="price_yearly" value="{{ old('price_yearly', $plan->price_yearly ?? '') }}" required></label>
    <label>Limite de projetos<input type="number" min="-1" name="limits[projects]" value="{{ old('limits.projects', $plan->limits['projects'] ?? -1) }}"><small>-1 mantém projetos ilimitados.</small></label>
    <label>Limite de membros<input type="number" min="-1" name="limits[members]" value="{{ old('limits.members', $plan->limits['members'] ?? -1) }}"><small>Inclui o proprietário. -1 significa ilimitados.</small></label>
</div>
<label>Descrição<textarea name="description" rows="3">{{ old('description', $plan->description ?? '') }}</textarea></label>
<div class="admin-form-actions"><a class="admin-button admin-button--secondary" href="{{ route('admin.plans.index') }}">Cancelar</a><button class="admin-button" type="submit">Salvar plano</button></div>
