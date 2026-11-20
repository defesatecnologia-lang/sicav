<?php
require_once __DIR__ . '/../inc/navbar.php';
?>

<section class="bg-body-tertiary min-vh-100 d-flex align-items-center">
    <div class="container">

        <div class="row justify-content-center align-items-center">

            <!-- FORMULÁRIO -->
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-5">

                        <div class="text-center mb-4">
                            <h3 class="fw-bold mb-1">Cadastrar Usuário</h3>
                            <p class="text-muted small mb-0">
                                Gerencie os acessos do sistema
                            </p>
                        </div>

                        <?php if (isset($_SESSION['nome_existe']) && $_SESSION['nome_existe']): ?>
                        <div class="alert alert-danger text-center small">
                            O nome escolhido já existe.
                        </div>
                        <?php unset($_SESSION['nome_existe']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['usuario_existe']) && $_SESSION['usuario_existe']): ?>
                        <div class="alert alert-danger text-center small">
                            O usuário escolhido já existe.
                        </div>
                        <?php unset($_SESSION['usuario_existe']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['status_cadastro']) && $_SESSION['status_cadastro']): ?>
                        <div class="alert alert-success text-center small">
                            Usuário cadastrado com sucesso!
                        </div>
                        <?php unset($_SESSION['status_cadastro']); ?>
                        <?php endif; ?>

                        <form action="?page=cadastroUsuario" method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                            <div class="form-floating mb-3">
                                <input type="text" id="text_nome" name="text_nome" class="form-control" placeholder="Nome" required>
                                <label for="text_nome">Nome</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" id="text_usuario" name="text_usuario" class="form-control" placeholder="Usuário"
                                    required>
                                <label for="text_usuario">Usuário</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" id="text_senha" name="text_senha" class="form-control" placeholder="Senha"
                                    required>
                                <label for="text_senha">Senha</label>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="text_nivel_acesso">Nível de Acesso</label>
                                <select id="text_nivel_acesso" name="text_nivel_acesso" class="form-select" required>
                                    <option value="user">Usuário</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>

                            <button type="submit" name="submit"
                                class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                                Cadastrar
                            </button>

                        </form>

                    </div>
                </div>
            </div>

            <!-- IMAGEM LATERAL -->
            <div class="col-12 col-md-6 col-lg-5 text-center mt-4 mt-md-0">
                <img src="<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/logo_sts.png" alt="Cadastro de usuário" class="img-fluid u-admin-logo-side">
            </div>

        </div>
        <!-- ============================================= -->
        <!-- SEÇÃO INFERIOR SIMPLES -->
        <!-- ============================================= -->
        <div class="row mt-5">
            <div class="col-12 text-center">
                <hr class="my-4">

                <div class="row g-3 justify-content-center">

                    <!-- TÍTULO -->
                    <div class="col-12">
                        <h5 class="fw-semibold mb-4">Ferramentas Administrativas</h5>
                    </div>

                    <!-- BOTÃO LISTAR USUÁRIOS (EXISTENTE) -->
                    <div class="col-md-3 col-sm-6">
                        <a href="?page=listarUsuario" class="btn btn-outline-primary w-100">
                            <i class="bi bi-people"></i> Listar Usuários
                        </a>
                    </div>

                    <!-- NOVO BOTÃO AUDITORIA -->
                    <div class="col-md-3 col-sm-6">
                        <a href="?page=admin_auditoria" class="btn btn-outline-success w-100">
                            <i class="bi bi-journal-text"></i> Relatório de Auditoria
                        </a>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <a href="?page=admin_login_locks" class="btn btn-outline-danger w-100">
                            <i class="bi bi-shield-lock"></i> Bloqueios de Login
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>


