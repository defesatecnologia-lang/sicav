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
    
    $sql = "UPDATE usuarios SET ativo = 1 WHERE id = :id";
    $params = [':id' => $id];
    $result = $db->update($sql, $params);
    
    if ($result['status'] === 'success') {
        $_SESSION['sucesso'] = 'Usuário ativado com sucesso!';
    } else {
        $_SESSION['erro'] = 'Erro ao ativar usuário!';
    }
}
header('Location: index.php?page=listarUsuario');
exit;
