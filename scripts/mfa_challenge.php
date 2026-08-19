<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../inc/mfa.php';
require_once __DIR__ . '/../config/database.php';

$pendingUserId = $_SESSION['mfa_pending_user_id'] ?? null;
$pendingUsername = $_SESSION['mfa_pending_username'] ?? null;
if (!$pendingUserId || !$pendingUsername) {
    header('Location: index.php?page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['mfa_pending_error'] = 'Sessao invalida. Recarregue a pagina e tente novamente.';
        header('Location: index.php?page=mfa_challenge');
        exit;
    }

    $code = trim($_POST['mfa_code'] ?? '');
    if ($code === '') {
        $_SESSION['mfa_pending_error'] = 'Informe o codigo de autenticacao.';
        header('Location: index.php?page=mfa_challenge');
        exit;
    }

    try {
        $db = new database();

        $result = $db->select(
            "SELECT * FROM usuarios WHERE id = :id LIMIT 1",
            [':id' => $pendingUserId]
        );

        if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
            clearLocalSessionState();
            header('Location: index.php?page=login');
            exit;
        }

        $usuario_db = $result['data'][0];

        $verified = false;
        if (preg_match('/^[A-F0-9]{10}$/', strtoupper($code))) {
            $verified = mfaUseRecoveryCode($db, $pendingUserId, $code);
        } else {
            $verified = mfaVerify($db, $pendingUserId, $code);
        }

        if (!$verified) {
            logEvent('MFA_FAILED', [
                'username' => $pendingUsername,
                'user_id' => $pendingUserId,
            ]);
            $_SESSION['mfa_pending_error'] = 'Codigo invalido ou expirado. Tente novamente.';
            header('Location: index.php?page=mfa_challenge');
            exit;
        }

        logEvent('MFA_SUCCESS', ['username' => $pendingUsername, 'user_id' => $pendingUserId]);
        unset(
            $_SESSION['mfa_pending_user_id'],
            $_SESSION['mfa_pending_username'],
            $_SESSION['mfa_pending_error']
        );

        finalizeLoginSession($db, $usuario_db);

        if ((int) $usuario_db->senha_temporaria === 1) {
            header('Location: index.php?page=trocar_senha');
            exit;
        }

        header('Location: index.php?page=home');
        exit;
    } catch (Throwable $e) {
        error_log('Erro no desafio MFA: ' . $e->getMessage());
        $_SESSION['mfa_pending_error'] = 'Falha interna ao validar o codigo. Contate o administrador.';
        header('Location: index.php?page=mfa_challenge');
        exit;
    }
}

$erro = $_SESSION['mfa_pending_error'] ?? null;
unset($_SESSION['mfa_pending_error']);
?>
<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
html,
body {
    height: 100%;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #1a2a4a url('<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/tela_fundo.png');
    background-size: 50% auto;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    font-family: "Segoe UI", Arial, sans-serif;
    box-shadow: inset 0 0 120px rgba(0, 0, 0, 0.6);
}
.login-wrapper {
    width: 100%;
    max-width: 440px;
    padding: 20px;
}
.card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(14px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
    color: #000;
}
.card h3 {
    font-weight: 600;
    text-align: center;
    letter-spacing: 1px;
}
.form-control {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}
.btn-secondary {
    background-color: #1f3c60;
    border: none;
    font-weight: 500;
}
.btn-secondary:hover {
    background-color: #152a45;
}
</style>
<div class="login-wrapper">
    <div class="card p-5">
        <h3>VERIFICAÇÃO EM DUAS ETAPAS</h3>
        <hr class="bg-light">
        <p class="text-center mb-3">Usuário: <strong><?= htmlspecialchars($pendingUsername, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p class="text-muted text-center small">Abra seu aplicativo autenticador e informe o código de 6 dígitos, ou use um código de recuperação.</p>
        <form action="?page=mfa_challenge" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label for="mfa_code">Código</label>
                <input type="text" id="mfa_code" name="mfa_code" class="form-control text-center" maxlength="10" autocomplete="one-time-code" inputmode="numeric" required autofocus>
            </div>
            <div class="form-group">
                <input type="submit" value="Validar" class="btn btn-secondary w-100 mt-2">
            </div>
        </form>
        <div class="text-center mt-3">
            <a href="index.php?page=logout">Cancelar e sair</a>
        </div>
        <?php if (!empty($erro)) : ?>
        <div class="alert alert-danger mt-3 text-center">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
    </div>
</div>