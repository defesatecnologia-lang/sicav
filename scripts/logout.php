<?php
// Garantir que a sessão está ativa
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/single_session.php';
require_once __DIR__ . '/../inc/log_event.php';
configureSessionSecurity();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LIMPEZA MÚLTIPLA PARA GARANTIR

// 0. Invalidar sessão única no banco (somente se este dispositivo ainda for o dono do token)
$usuarioId = currentUserId();
$sessionToken = $_SESSION['session_token'] ?? null;
if ($usuarioId) {
    logEvent('LOGOUT', ['reason' => 'manual']);
    try {
        $db = new database();
        invalidateUniqueSession($db, $usuarioId, $sessionToken);
    } catch (Throwable $e) {
        error_log('Erro ao invalidar sessao no logout: ' . $e->getMessage());
    }
}

// 1. Limpar todas as variáveis
clearLocalSessionState();


// 5. Redirecionar com headers anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

// Preserva o motivo do encerramento para a tela de login.
$timeoutParam = $_GET['timeout'] ?? null;
$isTimeout = $timeoutParam !== null && intval($timeoutParam) === 1;
$redirectQuery = $isTimeout ? 'timeout=1' : 'logout=1';
header('Location: ' . rtrim($GLOBALS['APP_PUBLIC_PATH'] ?? '/public', '/') . '/index.php?page=login&' . $redirectQuery);
exit;

