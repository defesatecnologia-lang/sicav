<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$erro = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;
$concurrent = isset($_GET['concurrent']) && $_GET['concurrent'] == 1;

?>
<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
/* Alerta institucional refinado */
.timeout-alert {
    border-left: 5px solid #d39e00;
    background: rgba(255, 193, 7, 0.15);
    backdrop-filter: blur(4px);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.95rem;
    animation: fadeSlideIn 0.4s ease forwards;
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.timeout-icon {
    font-size: 1.4rem;
}

.fade-out {
    opacity: 0;
    transform: translateY(-5px);
}

@keyframes fadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Reset estrutural */
html,
body {
    height: 100%;
    margin: 0;
    padding: 0;
}

/* Layout principal centralizado */
body {
    display: flex;
    justify-content: center;
    align-items: center;

    background: url('<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/tela_fundo.png');

    background-size: 50% auto;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;

    font-family: "Segoe UI", Arial, sans-serif;

    /* Moldura institucional discreta */
    box-shadow:
        inset 0 0 120px rgba(0, 0, 0, 0.6),
        inset 0 0 0 6px rgba(255, 255, 255, 0.08);
}

/* Wrapper evita conflito com Bootstrap */
.login-wrapper {
    width: 100%;
    max-width: 420px;
    padding: 20px;
}

/* Glassmorphism real */
.card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);

    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.25);

    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
    color: #000;
}

/* Título institucional */
.card h3 {
    font-weight: 600;
    text-align: center;
    letter-spacing: 1px;
}

/* Inputs refinados */
.form-control {
    background: rgba(255, 255, 255, 0.85);
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

/* Botão institucional */
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
        <h3>ACESSO AO SISTEMA</h3>
        <hr class="bg-light">
        <form action="?page=login_submit" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label for="text_usuario">Usuário</label>
                <input type="text" id="text_usuario" name="text_usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="text_senha">Senha</label>
                <input type="password" id="text_senha" name="text_senha" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" value="Entrar" class="btn btn-secondary w-100 mt-2">
            </div>
        </form>
        <!-- ERRO DE LOGIN -->
        <?php if (!empty($erro)) : ?>
        <div class="alert alert-danger mt-3 text-center">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
        <!-- TIMEOUT -->
        <?php if ($timeout): ?>
        <div id="timeoutAlert"
            class="alert alert-warning timeout-alert mt-3 text-center d-flex align-items-center justify-content-center gap-2">
            <span class="timeout-icon">⚠</span>
            <div>
                <strong>Sessão Expirada</strong><br>
                Por inatividade, faça login novamente.
            </div>
        </div>
        <?php endif; ?>
        <?php if ($concurrent): ?>
        <div class="alert alert-warning mt-3 text-center">
            Sua conta foi acessada em outro computador. Este dispositivo foi desconectado.
        </div>
        <?php endif; ?>
    </div>
</div>
<script nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener("DOMContentLoaded", function() {
    const usuarioInput = document.getElementById("text_usuario");
    const senhaInput = document.getElementById("text_senha");
    const alertBox = document.getElementById("timeoutAlert");
    const houveErroLogin = <?= !empty($erro) ? 'true' : 'false' ?>;

    if (houveErroLogin && usuarioInput && senhaInput) {
        usuarioInput.value = "";
        senhaInput.value = "";
        usuarioInput.focus();
    }

    if (alertBox) {
        setTimeout(() => {
            alertBox.classList.add("fade-out");
            setTimeout(() => {
                alertBox.remove();
            }, 600);

        }, 5000); // 5 segundos visível
    }
});
</script>