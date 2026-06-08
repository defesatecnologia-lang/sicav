<?php
require_once __DIR__ . '/../inc/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/usuarios_helpers.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']->nivel_acesso !== 'admin') {
    header('Location: index.php?page=nao_autorizado');
    exit;
}

$db = new database();
$listUsuarios = listarUsuarios($db);
?>
<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
body {
    margin: 0;
    padding: 0;
    background:
        linear-gradient(rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.55)),
        url('<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/tela_fundo.png');
    background-size: 40%;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    height: 100vh;
    box-shadow:
        inset 0 0 50px rgba(0, 0, 0, 0.4),
        0 0 20px rgba(0, 0, 0, 0.3);
}
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Lista de Usuarios</h2>
        <div>
            <a href="?page=administracao" class="btn btn-outline-primary" title="Voltar para o painel de administracao">
                <i class="fas fa-arrow-left"></i> Voltar para Administracao
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['sucesso'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?php unset($_SESSION['sucesso']); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['erro'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?php unset($_SESSION['erro']); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($listUsuarios)): ?>
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Usuario</th>
                    <th>Nivel de Acesso</th>
                    <th>Status</th>
                    <th>Data de Cadastro</th>
                    <th class="text-center">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listUsuarios as $usuario): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($usuario->id ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($usuario->nome ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?= htmlspecialchars($usuario->usuario ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <span class="badge <?= ($usuario->nivel_acesso === 'admin') ? 'bg-danger' : 'bg-primary' ?>">
                            <?= htmlspecialchars($usuario->nivel_acesso ?? 'user', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= ($usuario->ativo == 1) ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ($usuario->ativo == 1) ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($usuario->data ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="?page=editarUsuario&id=<?= (int)$usuario->id; ?>" class="btn btn-sm btn-outline-warning"
                                title="Editar usuario" data-bs-toggle="tooltip">
                                <i class="fas fa-edit"></i>
                            </a>

                            <?php if ($usuario->ativo == 1): ?>
                            <form action="?page=bloquearUsuario" method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= (int)$usuario->id; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary btn-confirm-action"
                                    title="Bloquear usuario" data-bs-toggle="tooltip"
                                    data-confirm="Tem certeza que deseja bloquear este usuario?">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form action="?page=ativarUsuario" method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= (int)$usuario->id; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success btn-confirm-action"
                                    title="Ativar usuario" data-bs-toggle="tooltip"
                                    data-confirm="Tem certeza que deseja ativar este usuario?">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                            <form action="?page=deletarUsuario" method="post" class="d-inline">
                                <input type="hidden" name="id" value="<?= (int)$usuario->id; ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-confirm-action"
                                    title="Excluir usuario permanentemente" data-bs-toggle="tooltip"
                                    data-confirm="Tem certeza que deseja excluir permanentemente este usuario?">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-info text-center py-4">
        <i class="fas fa-users fa-2x mb-3"></i><br>
        Nenhum usuario encontrado.
    </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="?page=administracao" class="btn btn-primary" title="Voltar para o painel de administracao">
            <i class="fas fa-arrow-left"></i> Voltar para Administracao
        </a>
    </div>
</div>

<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="confirmActionModalLabel">Confirmar acao</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="confirmActionModalMessage">Confirma esta acao?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmActionModalSubmit">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var modalEl = document.getElementById('confirmActionModal');
    var modalMsgEl = document.getElementById('confirmActionModalMessage');
    var modalSubmitBtn = document.getElementById('confirmActionModalSubmit');
    var confirmModal = new bootstrap.Modal(modalEl);
    var pendingForm = null;

    document.querySelectorAll('.btn-confirm-action').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var mensagem = btn.getAttribute('data-confirm') || 'Confirma esta acao?';
            pendingForm = btn.closest('form');
            modalMsgEl.textContent = mensagem;
            confirmModal.show();
        });
    });

    modalSubmitBtn.addEventListener('click', function() {
        if (pendingForm) {
            pendingForm.submit();
        }
    });
});
</script>
