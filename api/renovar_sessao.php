<?php
require_once __DIR__ . '/../config/app.php';
configureSessionSecurity();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Metodo nao permitido', 405);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
if (!validateCsrfToken($csrfToken)) {
    apiError('CSRF token invalido', 419);
}

$tempo_maximo = SESSION_TIMEOUT;

if (!isset($_SESSION['usuario'])) {
    apiError('Sessao nao encontrada', 401);
}

$usuarioId = currentUserId();
$sessionToken = $_SESSION['session_token'] ?? null;
try {
    $db = new database();
    $valid = $usuarioId && $sessionToken && isUniqueSessionValid($db, $usuarioId, $sessionToken);
} catch (Throwable $e) {
    logApiException($e, ['context' => 'renovar_sessao']);
    $valid = false;
}

if (!$valid) {
    clearLocalSessionState();
    apiJsonResponse([
        'success' => false,
        'reason' => 'concurrent_login',
        'error' => 'Sua conta foi acessada em outro dispositivo.'
    ], 401);
}

$now = time();
if (!isset($_SESSION['ultimo_acesso'])) {
    $_SESSION['ultimo_acesso'] = $now;
}

$inatividade = $now - (int) $_SESSION['ultimo_acesso'];
$time_left = $tempo_maximo - $inatividade;

if ($time_left <= 0) {
    invalidateUniqueSession($db, $usuarioId, $sessionToken);
    clearLocalSessionState();
    apiError('Sessao expirada', 401);
}

$_SESSION['ultimo_acesso'] = $now;

if (!isset($_SESSION['last_regenerate']) || ($now - (int) $_SESSION['last_regenerate']) >= 300) {
    session_regenerate_id(true);
    $_SESSION['last_regenerate'] = $now;
    registerUniqueSession($db, $usuarioId, $sessionToken);
}

apiJsonResponse([
    'success' => true,
    'message' => 'Sessao renovada',
    'time_left' => $tempo_maximo
]);
