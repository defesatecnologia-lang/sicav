<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = 'Metodo invalido para esta operacao.';
    header('Location: index.php?page=admin_login_locks');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['erro'] = 'Token de seguranca invalido.';
    header('Location: index.php?page=admin_login_locks');
    exit;
}

$modo = $_POST['modo'] ?? 'registro';
$ip = trim($_POST['ip'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');

if ($ip === '') {
    $_SESSION['erro'] = 'IP nao informado para desbloqueio.';
    header('Location: index.php?page=admin_login_locks');
    exit;
}

$db = new database();

if ($modo === 'ip') {
    $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
    $result = $db->update($sql, [':ip' => $ip]);

    if (($result['status'] ?? '') === 'success') {
        $_SESSION['sucesso'] = 'Bloqueios do IP removidos com sucesso.';
    } else {
        $_SESSION['erro'] = 'Erro ao desbloquear IP.';
    }

    header('Location: index.php?page=admin_login_locks');
    exit;
}

if ($usuario === '') {
    $_SESSION['erro'] = 'Usuario nao informado para desbloqueio do registro.';
    header('Location: index.php?page=admin_login_locks');
    exit;
}

$sql = "DELETE FROM login_attempts WHERE usuario = :usuario AND ip_address = :ip";
$result = $db->update($sql, [':usuario' => $usuario, ':ip' => $ip]);

if (($result['status'] ?? '') === 'success') {
    $_SESSION['sucesso'] = 'Registro de bloqueio removido com sucesso.';
} else {
    $_SESSION['erro'] = 'Erro ao desbloquear registro.';
}

header('Location: index.php?page=admin_login_locks');
exit;
