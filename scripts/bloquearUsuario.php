<?php
require_once __DIR__ . '/../config/database.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro'] = 'Metodo invalido para esta operacao.';
    header('Location: index.php?page=listarUsuario');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['erro'] = 'Token de seguranca invalido.';
    header('Location: index.php?page=listarUsuario');
    exit;
}
if (isset($_POST['id'])) {
    $db = new database();
    $id = (int)$_POST['id'];

    if ($id === $_SESSION['usuario']->id) {
        $_SESSION['erro'] = 'Você não pode bloquear seu próprio usuário!';
        header('Location: index.php?page=listarUsuario');
        exit;
    }
    
    $sql = "UPDATE usuarios SET ativo = 0 WHERE id = :id";
    $params = [':id' => $id];
    $result = $db->update($sql, $params);
    
    if ($result['status'] === 'success') {
        $_SESSION['sucesso'] = 'Usuário bloqueado com sucesso!';
    } else {
        $_SESSION['erro'] = 'Erro ao bloquear usuário!';
    }
}
header('Location: index.php?page=listarUsuario');
exit;
