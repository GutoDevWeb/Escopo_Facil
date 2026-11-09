@extends('layouts.system')
@section('content')
<section class="configuracao">

    <!-- Header -->
    <div class="configuracao-header">
        <h1 class="configuracao-title">Configuração</h1>
        <div class="configuracao-header-right">
            @include('system.notification-bell')
            <div class="configuracao-user user-menu-trigger" style="padding:6px 8px;border-radius:8px">
                <?= \App\Support\SystemAvatar::renderAvatar($avatarStr, $usuario['nome'], 'configuracao-avatar') ?>
                <div class="configuracao-user-info">
                    <span class="configuracao-user-name"><?= htmlspecialchars($usuario['nome']) ?></span>
                    <span class="configuracao-user-role"><?= htmlspecialchars($usuario['cargo']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo principal -->
    <div class="configuracao-body">
        @include('system.plan-overview')

        <!-- Card: Dados pessoais -->
        <div class="configuracao-card">
            <div class="configuracao-card-header">
                <h2 class="configuracao-card-title">Dados pessoais</h2>
                <p class="configuracao-card-desc">Atualize suas informações de perfil.</p>
            </div>

            <form class="configuracao-form" action="{{ url('/system') }}/actions/usuario/atualizar.php" method="POST">
@csrf

                <div class="configuracao-form-row">
                    <div class="form-group">
                        <label for="config-nome">Nome<span class="required">*</span></label>
                        <input type="text" id="config-nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="config-cargo">Cargo<span class="required">*</span></label>
                        <div class="input-select-wrapper">
                            <select id="config-cargo" name="cargo">
                                <option value="" disabled>Selecione seu cargo</option>
                                <option value="dev-frontend" <?= $cargoSelecionado($usuario['cargo'], 'dev-frontend') ?>>Desenvolvedor Frontend</option>
                                <option value="dev-backend" <?= $cargoSelecionado($usuario['cargo'], 'dev-backend') ?>>Desenvolvedor Backend</option>
                                <option value="dev-fullstack" <?= $cargoSelecionado($usuario['cargo'], 'dev-fullstack') ?>>Desenvolvedor Full Stack</option>
                                <option value="ui-ux-designer" <?= $cargoSelecionado($usuario['cargo'], 'ui-ux-designer') ?>>UI/UX Designer</option>
                                <option value="product-owner" <?= $cargoSelecionado($usuario['cargo'], 'product-owner') ?>>Product Owner</option>
                                <option value="scrum-master" <?= $cargoSelecionado($usuario['cargo'], 'scrum-master') ?>>Scrum Master</option>
                                <option value="tech-lead" <?= $cargoSelecionado($usuario['cargo'], 'tech-lead') ?>>Tech Lead</option>
                                <option value="qa-testes" <?= $cargoSelecionado($usuario['cargo'], 'qa-testes') ?>>QA / Testes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="config-email">Email<span class="required">*</span></label>
                    <input type="email" id="config-email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>">
                </div>

                <div class="configuracao-form-actions">
                    <button type="button" class="configuracao-btn-cancelar">Cancelar</button>
                    <button type="submit" class="configuracao-btn-salvar">Salvar alterações</button>
                </div>
            </form>
        </div>

        <!-- Card: Alterar senha -->
        <div class="configuracao-card">
            <div class="configuracao-card-header">
                <h2 class="configuracao-card-title">Alterar senha</h2>
                <p class="configuracao-card-desc">Para sua segurança, recomendamos uma senha forte.</p>
            </div>

            <form class="configuracao-form" action="{{ url('/system') }}/actions/usuario/alterar-senha.php" method="POST">
@csrf

                <div class="form-group">
                    <label for="config-senha-atual">Senha atual<span class="required">*</span></label>
                    <div class="input-password-wrapper">
                        <input type="password" id="config-senha-atual" name="senha_atual" placeholder="Digite sua senha atual">
                        <button type="button" class="btn-toggle-password" data-target="config-senha-atual" aria-label="Mostrar senha">
                            <img src="{{ url('/system') }}/assets/icon/eye-off.svg" alt="Mostrar senha" width="24" height="24">
                        </button>
                    </div>
                </div>

                <div class="configuracao-form-row">
                    <div class="form-group">
                        <label for="config-nova-senha">Nova senha<span class="required">*</span></label>
                        <div class="input-password-wrapper">
                            <input type="password" id="config-nova-senha" name="nova_senha" placeholder="Digite a nova senha" minlength="8">
                            <button type="button" class="btn-toggle-password" data-target="config-nova-senha" aria-label="Mostrar senha">
                                <img src="{{ url('/system') }}/assets/icon/eye-off.svg" alt="Mostrar senha" width="24" height="24">
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="config-confirmar-senha">Confirmar senha<span class="required">*</span></label>
                        <div class="input-password-wrapper">
                            <input type="password" id="config-confirmar-senha" name="confirmar_nova_senha" placeholder="Confirme a nova senha" minlength="8">
                            <button type="button" class="btn-toggle-password" data-target="config-confirmar-senha" aria-label="Mostrar senha">
                                <img src="{{ url('/system') }}/assets/icon/eye-off.svg" alt="Mostrar senha" width="24" height="24">
                            </button>
                        </div>
                    </div>
                </div>

                <div class="configuracao-form-actions">
                    <button type="button" class="configuracao-btn-cancelar">Cancelar</button>
                    <button type="submit" class="configuracao-btn-salvar">Alterar senha</button>
                </div>
            </form>
        </div>

        <!-- Card: Avatar -->
        <div class="configuracao-card">
            <div class="configuracao-card-header">
                <h2 class="configuracao-card-title">Avatar</h2>
                <p class="configuracao-card-desc">Escolha uma cor e um padrão para seu perfil.</p>
            </div>

            <form class="configuracao-form" action="{{ url('/system') }}/actions/usuario/atualizar-avatar.php" method="POST">
@csrf

                <?= \App\Support\SystemAvatar::avatarPickerHtml($avatarStr) ?>
                <div class="configuracao-form-actions">
                    <button type="button" class="configuracao-btn-cancelar">Cancelar</button>
                    <button type="submit" class="configuracao-btn-salvar">Salvar avatar</button>
                </div>
            </form>
        </div>

        <section class="configuracao-card configuracao-app" aria-labelledby="config-app-title">
            <img src="{{ asset('icons/icon-192.png') }}" alt="" width="64" height="64">
            <div class="configuracao-app-copy">
                <h2 class="configuracao-card-title" id="config-app-title">Baixe nosso app</h2>
                <p class="configuracao-card-desc">Tenha o Escopo Fácil na tela inicial do seu dispositivo e acesse seus projetos com mais praticidade.</p>
                <p class="configuracao-app-note">Instalação pelo navegador. É necessário estar conectado à internet.</p>
            </div>
            <button class="configuracao-app-install" type="button" data-app-install>Baixe nosso app</button>
        </section>

        <!-- Card: Zona de perigo -->
        <div class="configuracao-card configuracao-card--danger">
            <div class="configuracao-card-header">
                <h2 class="configuracao-card-title">Zona de perigo</h2>
                <p class="configuracao-card-desc">Ações irreversíveis para sua conta.</p>
            </div>

            <div class="configuracao-danger-actions">
                <div class="configuracao-danger-item">
                    <div class="configuracao-danger-info">
                        <span class="configuracao-danger-label">Excluir conta</span>
                        <span class="configuracao-danger-desc">Ao excluir sua conta, todos os seus dados serão removidos permanentemente.</span>
                    </div>
                    <button type="button" class="configuracao-btn-excluir" id="open-delete-account">Excluir conta</button>
                </div>
            </div>
        </div>

    </div>
</section>

<dialog id="delete-account-dialog" class="system-dialog" @if($errors->userDeletion->any()) data-reopen @endif aria-labelledby="delete-account-title">
    <form method="POST" action="{{ route('profile.destroy') }}" class="admin-form">
        @csrf @method('DELETE')
        <h2 id="delete-account-title">Excluir sua conta?</h2>
        <p class="admin-muted">Essa ação é permanente. Confirme sua senha para excluir sua conta e os dados vinculados a ela.</p>
        <label for="delete-account-password">Confirme sua senha</label>
        <input type="password" id="delete-account-password" name="password" required autocomplete="current-password" autofocus>
        @foreach($errors->userDeletion->all() as $error)<p class="admin-field-error" role="alert">{{ $error }}</p>@endforeach
        <div class="admin-form-actions">
            <button type="button" class="admin-button admin-button--secondary" id="cancel-delete-account">Cancelar</button>
            <button type="submit" class="admin-button admin-button--danger">Excluir minha conta</button>
        </div>
    </form>
</dialog>
@endsection
