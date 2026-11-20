<?php
require_once __DIR__ . '/../inc/navbar.php';
require_once __DIR__ . '/../config/database.php';

$db = new database();

$sql = "SELECT usuario, ip_address, attempt_count, lock_until, updated_at
        FROM login_attempts
        ORDER BY
            CASE WHEN lock_until IS NOT NULL AND lock_until > NOW() THEN 0 ELSE 1 END,
            updated_at DESC";
$result = $db->select($sql);
$locks = ($result['status'] ?? '') === 'success' ? $result['data'] : [];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Bloqueios de Login</h2>
        <a href="?page=administracao" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
    <!-- <div class="alert alert-info py-2">
        <strong>Como usar:</strong> "Desbloquear este registro" remove apenas a linha atual. "Desbloquear todo o IP" remove todos os bloqueios daquele IP.
    </div> -->

    <?php if (isset($_SESSION['sucesso'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['sucesso'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['sucesso']); endif; ?>

    <?php if (isset($_SESSION['erro'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($_SESSION['erro'], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['erro']); endif; ?>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Escopo</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Tentativas</th>
                    <th>Bloqueado ate</th>
                    <th>Status</th>
                    <th>Atualizado em</th>
                    <th class="text-center">Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locks)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4">Nenhum bloqueio registrado.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($locks as $row): ?>
                <?php
                    $isGlobalIp = ($row->usuario ?? '') === GLOBAL_IP_SCOPE;
                    $lockTs = !empty($row->lock_until) ? strtotime((string)$row->lock_until) : false;
                    $isActive = $lockTs !== false && $lockTs > time();
                    ?>
                <tr>
                    <td><?= $isGlobalIp ? 'IP Global' : 'Usuario + IP' ?></td>
                    <td><?= $isGlobalIp ? '-' : htmlspecialchars($row->usuario ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row->ip_address ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($row->attempt_count ?? 0) ?></td>
                    <td><?= !empty($row->lock_until) ? htmlspecialchars($row->lock_until, ENT_QUOTES, 'UTF-8') : '-' ?>
                    </td>
                    <td>
                        <span class="badge <?= $isActive ? 'bg-danger' : 'bg-secondary' ?>">
                            <?= $isActive ? 'Ativo' : 'Expirado' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row->updated_at ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center">
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <form action="?page=desbloquear_login" method="post" class="d-inline">
                                <input type="hidden" name="modo" value="registro">
                                <input type="hidden" name="usuario" value="<?= htmlspecialchars((string)($row->usuario ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="ip" value="<?= htmlspecialchars((string)($row->ip_address ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success btn-confirm-action"
                                    data-confirm="Desbloquear somente este registro (usuario + IP). Confirma?">
                                    Desbloquear registro
                                </button>
                            </form>
                            <form action="?page=desbloquear_login" method="post" class="d-inline">
                                <input type="hidden" name="modo" value="ip">
                                <input type="hidden" name="ip" value="<?= htmlspecialchars((string)($row->ip_address ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-confirm-action"
                                    data-confirm="Desbloquear TODOS os registros deste IP. Confirma?">
                                    Desbloquear IP
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
    const modalEl = document.getElementById('confirmActionModal');
    const modalMsgEl = document.getElementById('confirmActionModalMessage');
    const modalSubmitBtn = document.getElementById('confirmActionModalSubmit');
    const confirmModal = new bootstrap.Modal(modalEl);
    let pendingForm = null;

    document.querySelectorAll('.btn-confirm-action').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const msg = btn.getAttribute('data-confirm') || 'Confirma a acao?';
            pendingForm = btn.closest('form');
            modalMsgEl.textContent = msg;
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
