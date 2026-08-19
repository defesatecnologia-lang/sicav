<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../inc/log_event.php';
require_once __DIR__ . '/../inc/rate_limit.php';
require_once __DIR__ . '/../inc/login_security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?page=login');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    setLoginErrorAndRedirect('Sessao invalida. Recarregue a pagina e tente novamente.');
}

$usuario = trim($_POST['text_usuario'] ?? '');
$senha = $_POST['text_senha'] ?? '';
$ipAddress = getClientIp();

if ($usuario === '' || $senha === '') {
    header('Location: index.php?page=login');
    exit;
}

$db = new database();
cleanupOldLoginAttempts($db);
purgeExpiredSessions($db);
cleanupOldRateLimits($db);

$attempt = getLoginAttempt($db, $usuario, $ipAddress);
if ($attempt['lock_until_ts'] > time()) {
    $restante = $attempt['lock_until_ts'] - time();
    $minutos = (int)ceil($restante / 60);
    setLoginErrorAndRedirect("Acesso temporariamente bloqueado. Tente novamente em {$minutos} minuto(s).");
}

$ipAttempt = getLoginAttempt($db, GLOBAL_IP_SCOPE, $ipAddress);
if ($ipAttempt['lock_until_ts'] > time()) {
    $restante = $ipAttempt['lock_until_ts'] - time();
    $minutos = (int)ceil($restante / 60);
    setLoginErrorAndRedirect("IP temporariamente bloqueado por excesso de tentativas. Tente novamente em {$minutos} minuto(s).");
}

$params = [':usuario' => $usuario];
$sql = "SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1";
$result = $db->select($sql, $params);

if (($result['status'] ?? '') === 'error') {
    header('Location: index.php?page=404');
    exit;
}

$isInvalid = count($result['data']) === 0 || !password_verify($senha, $result['data'][0]->senha);
$isInactive = !$isInvalid && (int)($result['data'][0]->ativo ?? 0) !== 1;
if ($isInactive) {
    logEvent('LOGIN_FAILURE', ['username' => $usuario, 'reason' => 'account_inactive']);
    setLoginErrorAndRedirect('Usuario bloqueado ou sem permissao de acesso. Contate o administrador.');
}

if ($isInvalid) {
    $failure = registerLoginFailure($db, $usuario, $ipAddress, (int)$attempt['attempt_count'], MAX_LOGIN_ATTEMPTS);
    $ipFailure = registerLoginFailure($db, GLOBAL_IP_SCOPE, $ipAddress, (int)$ipAttempt['attempt_count'], MAX_IP_LOGIN_ATTEMPTS);

    logEvent('LOGIN_FAILURE', [
        'username' => $usuario,
        'reason' => 'invalid_credentials',
        'attempt_count' => $failure['attempt_count'],
        'ip_locked' => $ipFailure['locked'],
    ]);

    if ($ipFailure['locked']) {
        logEvent('LOGIN_FAILURE', ['username' => $usuario, 'reason' => 'ip_locked']);
        setLoginErrorAndRedirect('IP bloqueado por excesso de tentativas. Acesso bloqueado por 15 minutos.');
    }

    if ($failure['locked']) {
        logEvent('LOGIN_FAILURE', ['username' => $usuario, 'reason' => 'account_locked']);
        setLoginErrorAndRedirect('Numero maximo de tentativas excedido. Acesso bloqueado por 15 minutos.');
    }

    $restantes = max(0, MAX_LOGIN_ATTEMPTS - (int)$failure['attempt_count']);
    setLoginErrorAndRedirect("Usuario ou senha invalidos. Restam {$restantes} tentativa(s).");
}

$usuario_db = $result['data'][0];
clearLoginAttempts($db, $usuario, $ipAddress);
clearLoginAttempts($db, GLOBAL_IP_SCOPE, $ipAddress);

logEvent('LOGIN_SUCCESS', ['username' => $usuario_db->usuario]);

require_once __DIR__ . '/../inc/mfa.php';

// Se a conta tem MFA habilitado, exige desafio TOTP antes de concluir o login.
if (mfaIsEnabled($db, (int) $usuario_db->id)) {
    $_SESSION['mfa_pending_user_id'] = (int) $usuario_db->id;
    $_SESSION['mfa_pending_username'] = $usuario_db->usuario;
    unset($_SESSION['mfa_pending_error']);
    header('Location: index.php?page=mfa_challenge');
    exit;
}

finalizeLoginSession($db, $usuario_db);

if ((int)$usuario_db->senha_temporaria === 1) {
    header('Location: index.php?page=trocar_senha');
    exit;
}

header('Location: index.php?page=home');
