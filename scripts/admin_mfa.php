<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/mfa.php';
require_once __DIR__ . '/../inc/rate_limit.php';

$db = new database();
$usuarioId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['mfa_admin_error'] = 'Sessao invalida. Recarregue a pagina.';
        header('Location: index.php?page=admin_mfa');
        exit;
    }

    try {
        enforceRateLimit($db, 'admin_mfa', 10, 60);
    } catch (Throwable $e) {
        error_log('Erro no rate limit admin_mfa: ' . $e->getMessage());
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'setup') {
        $secret = mfaGenerateSecret();
        try {
            mfaCreatePending($db, $usuarioId, $secret);
            $otpauthUri = mfaOtpauthUri($secret, $_SESSION['usuario']->usuario ?? 'usuario', APP_NAME);
            $recoveryCodes = mfaGenerateRecoveryCodes();
            mfaStoreRecoveryCodes($db, $usuarioId, $recoveryCodes);
            $_SESSION['mfa_pending_setup'] = [
                'secret' => $secret,
                'otpauth' => $otpauthUri,
                'recovery_codes' => $recoveryCodes,
            ];
            logEvent('MFA_SETUP_STARTED', ['user_id' => $usuarioId]);
        } catch (Throwable $e) {
            error_log('Erro ao iniciar MFA: ' . $e->getMessage());
            $_SESSION['mfa_admin_error'] = 'Falha ao iniciar configuracao. Verifique se VISITORPASS_MFA_KEY esta definido.';
        }
        header('Location: index.php?page=admin_mfa');
        exit;
    }

    if ($acao === 'confirm') {
        $code = trim($_POST['mfa_code'] ?? '');
        $row = mfaGetForUser($db, $usuarioId);
        if ($row === null) {
            $_SESSION['mfa_admin_error'] = 'Nenhuma configuracao pendente.';
            header('Location: index.php?page=admin_mfa');
            exit;
        }

        try {
            $secret = mfaDecryptSecret((string) $row->secret);
            if (mfaVerifyCode($secret, $code)) {
                mfaEnable($db, $usuarioId);
                logEvent('MFA_ENABLED', ['user_id' => $usuarioId]);
                $_SESSION['mfa_admin_success'] = 'Autenticacao em duas etapas ativada. Guarde seus codigos de recuperacao.';
            } else {
                logEvent('MFA_FAILED', ['user_id' => $usuarioId, 'context' => 'setup_confirm']);
                $_SESSION['mfa_admin_error'] = 'Codigo invalido. O codigo nao confere com o secret gerado.';
            }
        } catch (Throwable $e) {
            error_log('Erro ao confirmar MFA: ' . $e->getMessage());
            $_SESSION['mfa_admin_error'] = 'Falha ao confirmar o codigo.';
        }
        header('Location: index.php?page=admin_mfa');
        exit;
    }

    if ($acao === 'disable') {
        $password = $_POST['senha_atual'] ?? '';
        $result = $db->select(
            "SELECT senha FROM usuarios WHERE id = :id LIMIT 1",
            [':id' => $usuarioId]
        );
        $hash = ($result['status'] === 'success' && !empty($result['data'])) ? $result['data'][0]->senha : null;

        if (!$hash || !password_verify($password, (string) $hash)) {
            $_SESSION['mfa_admin_error'] = 'Senha atual incorreta.';
            header('Location: index.php?page=admin_mfa');
            exit;
        }

        mfaDisable($db, $usuarioId);
        $db->delete("DELETE FROM mfa_recovery_codes WHERE usuario_id = :usuario_id", [':usuario_id' => $usuarioId]);
        logEvent('MFA_DISABLED', ['user_id' => $usuarioId]);
        $_SESSION['mfa_admin_success'] = 'Autenticacao em duas etapas desativada.';
        header('Location: index.php?page=admin_mfa');
        exit;
    }
}

$mfaRow = mfaGetForUser($db, $usuarioId);
$mfaEnabled = $mfaRow !== null && (int) ($mfaRow->enabled ?? 0) === 1;
$recoveryCount = mfaRemainingRecoveryCodes($db, $usuarioId);
$pendingSetup = $_SESSION['mfa_pending_setup'] ?? null;
$erro = $_SESSION['mfa_admin_error'] ?? null;
$sucesso = $_SESSION['mfa_admin_success'] ?? null;
unset($_SESSION['mfa_admin_error'], $_SESSION['mfa_admin_success']);
?>
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Autenticação em Duas Etapas (MFA)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($sucesso)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <?php if ($mfaEnabled): ?>
                        <p class="alert alert-success">
                            <strong>MFA ativo.</strong> Sua conta exige código de verificação no login.
                            Códigos de recuperação restantes: <strong><?= (int) $recoveryCount ?></strong>.
                        </p>
                        <h6 class="mt-4">Desativar MFA</h6>
                        <form method="post" action="?page=admin_mfa" class="row g-2 align-items-center">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="acao" value="disable">
                            <div class="col-md-6">
                                <input type="password" name="senha_atual" class="form-control" placeholder="Senha atual (confirmação)" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-danger">Desativar</button>
                            </div>
                        </form>
                    <?php elseif (!empty($pendingSetup)): ?>
                        <h6 class="text-danger">1. Escaneie ou digite o secret no autenticador</h6>
                        <p><strong>Secret (base32):</strong> <code><?= htmlspecialchars($pendingSetup['secret'], ENT_QUOTES, 'UTF-8') ?></code></p>
                        <p><strong>URI:</strong> <code class="small text-break"><?= htmlspecialchars($pendingSetup['otpauth'], ENT_QUOTES, 'UTF-8') ?></code></p>
                        <hr>
                        <h6 class="text-danger">2. Informe o código gerado pelo autenticador</h6>
                        <form method="post" action="?page=admin_mfa" class="row g-2 align-items-center">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="acao" value="confirm">
                            <div class="col-md-5">
                                <input type="text" name="mfa_code" class="form-control" placeholder="Código de 6 dígitos" maxlength="6" inputmode="numeric" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success">Confirmar e ativar</button>
                            </div>
                        </form>
                        <hr>
                        <h6 class="text-danger">3. Guarde seus códigos de recuperação</h6>
                        <div class="alert alert-warning small">
                            Cada código pode ser usado uma única vez. Guarde-os em local seguro.
                        </div>
                        <div class="row">
                            <?php foreach ($pendingSetup['recovery_codes'] as $rc): ?>
                                <div class="col-6"><code><?= htmlspecialchars($rc, ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Sua conta ainda não possui verificação em duas etapas.</p>
                        <form method="post" action="?page=admin_mfa">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="acao" value="setup">
                            <button type="submit" class="btn btn-primary">Configurar MFA</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>