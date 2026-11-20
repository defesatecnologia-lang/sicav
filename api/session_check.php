<?php
require_once __DIR__ . '/../config/app.php';
configureSessionSecurity();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/api_bootstrap.php';

if (!isset($_SESSION['usuario'])) {
    apiJsonResponse(['status' => 'expired', 'success' => false, 'error' => 'Sessao nao encontrada'], 401);
}

$usuarioId = currentUserId();
$sessionToken = $_SESSION['session_token'] ?? null;
try {
    $db = new database();
    $valid = $usuarioId && $sessionToken && isUniqueSessionValid($db, $usuarioId, $sessionToken);
} catch (Throwable $e) {
    logApiException($e, ['context' => 'session_check']);
    $valid = false;
}

if (!$valid) {
    clearLocalSessionState();
    apiJsonResponse([
        'success' => false,
        'status' => 'expired',
        'reason' => 'concurrent_login',
        'error' => 'Sua conta foi acessada em outro dispositivo.'
    ], 401);
}

$tempo_maximo = SESSION_TIMEOUT;
$now = time();

if (!isset($_SESSION['ultimo_acesso'])) {
    $_SESSION['ultimo_acesso'] = $now;
    apiJsonResponse(['status' => 'active', 'time_left' => $tempo_maximo]);
}

$inatividade = $now - (int) $_SESSION['ultimo_acesso'];

if ($inatividade >= $tempo_maximo) {
    invalidateUniqueSession($db, $usuarioId, $sessionToken);
    clearLocalSessionState();
    apiJsonResponse(['status' => 'expired', 'success' => false, 'error' => 'Sessao expirada por inatividade'], 401);
}

$tempo_restante = max(0, $tempo_maximo - $inatividade);
apiJsonResponse(['status' => 'active', 'time_left' => $tempo_restante]);
