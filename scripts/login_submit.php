<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../inc/log_event.php';
require_once __DIR__ . '/../inc/rate_limit.php';

const MAX_LOGIN_ATTEMPTS = 5;
const MAX_IP_LOGIN_ATTEMPTS = 15;
const LOGIN_LOCK_SECONDS = 900; // 15 minutos
function getLoginAttempt(database $db, string $usuario, string $ip): array
{
    $sql = "SELECT attempt_count, lock_until
            FROM login_attempts
            WHERE usuario = :usuario AND ip_address = :ip
            LIMIT 1";
    $result = $db->select($sql, [':usuario' => $usuario, ':ip' => $ip]);

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return ['attempt_count' => 0, 'lock_until_ts' => 0];
    }

    $row = $result['data'][0];
    $lockUntilTs = 0;
    if (!empty($row->lock_until)) {
        $parsed = strtotime((string)$row->lock_until);
        $lockUntilTs = $parsed !== false ? $parsed : 0;
    }

    return [
        'attempt_count' => (int)($row->attempt_count ?? 0),
        'lock_until_ts' => $lockUntilTs
    ];
}

function registerLoginFailure(database $db, string $usuario, string $ip, int $currentCount, int $maxAttempts): array
{
    $newCount = $currentCount + 1;
    $shouldLock = $newCount >= $maxAttempts;
    $lockUntil = $shouldLock ? date('Y-m-d H:i:s', time() + LOGIN_LOCK_SECONDS) : null;

    $sql = "INSERT INTO login_attempts (usuario, ip_address, attempt_count, lock_until)
            VALUES (:usuario, :ip, :attempt_count, :lock_until)
            ON DUPLICATE KEY UPDATE
                attempt_count = VALUES(attempt_count),
                lock_until = VALUES(lock_until),
                updated_at = CURRENT_TIMESTAMP";

    $db->insert($sql, [
        ':usuario' => $usuario,
        ':ip' => $ip,
        ':attempt_count' => $newCount,
        ':lock_until' => $lockUntil
    ]);

    return ['attempt_count' => $newCount, 'locked' => $shouldLock];
}

function clearLoginAttempts(database $db, string $usuario, string $ip): void
{
    $sql = "DELETE FROM login_attempts WHERE usuario = :usuario AND ip_address = :ip";
    $db->delete($sql, [':usuario' => $usuario, ':ip' => $ip]);
}

function cleanupOldLoginAttempts(database $db): void
{
    $sql = "DELETE FROM login_attempts
            WHERE (lock_until IS NOT NULL AND lock_until < NOW())
               OR updated_at < (NOW() - INTERVAL 7 DAY)";
    $db->delete($sql);
}

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
