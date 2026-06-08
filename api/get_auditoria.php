<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('admin');

try {
    $db = new database();
    $registroId = isset($_GET['registro_id']) ? (int) $_GET['registro_id'] : 0;

    $sql = "SELECT a.*, u.nome as usuario_nome
            FROM auditoria_visitantes a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE 1=1";
    $params = [];

    if ($registroId > 0) {
        $sql .= " AND a.registro_id = :registro_id";
        $params[':registro_id'] = $registroId;
    }

    $sql .= " ORDER BY a.data_acao DESC LIMIT 100";
    $result = $db->query($sql, $params);
    if ($result['success'] !== true) {
        apiJsonResponse([], 200);
    }

    $auditoria = array_map(static fn($item) => (array) $item, $result['data']);
    apiJsonResponse($auditoria);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro interno', 500);
}
