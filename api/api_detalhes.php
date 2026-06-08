<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('supervisor');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    apiError('ID nao informado', 400);
}

try {
    $db = new database();
    $result = $db->query("SELECT * FROM visitorinfo WHERE id = :id", [':id' => $id]);
    if ($result['success'] !== true || count($result['data']) === 0) {
        apiError('Visitante nao encontrado', 404);
    }

    $dados = (array) $result['data'][0];
    $dados['foto'] = !empty($dados['foto_caminho']) ? $dados['foto_caminho'] : null;
    $fotoExiste = false;
    if (!empty($dados['foto'])) {
        $fotoExiste = file_exists(__DIR__ . '/../' . $dados['foto']);
    }

    apiJsonResponse([
        'success' => true,
        'dados' => $dados,
        'foto_existe' => $fotoExiste,
    ]);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro interno', 500);
}
