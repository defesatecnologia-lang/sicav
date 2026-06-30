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
        $_SESSION['erro'] = 'Você não pode excluir seu próprio usuário!';
        header('Location: index.php?page=listarUsuario');
        exit;
    }

    $result_admin_count = $db->select(
        "SELECT COUNT(*) AS total FROM usuarios WHERE nivel_acesso = 'admin' AND ativo = 1"
    );
    $admin_count = ($result_admin_count['status'] === 'success' && !empty($result_admin_count['data']))
        ? (int) $result_admin_count['data'][0]->total
        : 0;

    $target_is_admin = $db->select(
        "SELECT nivel_acesso FROM usuarios WHERE id = :id",
        [':id' => $id]
    );
    $target_is_admin = ($target_is_admin['status'] === 'success' && !empty($target_is_admin['data']))
        ? ($target_is_admin['data'][0]->nivel_acesso === 'admin')
        : false;

    if ($target_is_admin && $admin_count <= 1) {
        $_SESSION['erro'] = 'Não é possível excluir o último administrador do sistema!';
        header('Location: index.php?page=listarUsuario');
        exit;
    }

    $sql = "DELETE FROM usuarios WHERE id = :id";
    $params = [':id' => $id];
    $result = $db->delete($sql, $params);
    
    if ($result['status'] === 'success') {
        $_SESSION['sucesso'] = 'Usuário excluído com sucesso!';
    } else {
        $_SESSION['erro'] = 'Erro ao excluir usuário!';
    }
}
header('Location: index.php?page=listarUsuario');
exit;
