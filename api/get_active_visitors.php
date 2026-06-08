<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('user');

try {
    $db = new database();
    $result = $db->query("
        SELECT 
            id,
            nome_completo as nome,
            numero_cpf as cpf,
            veiculo_placa as placa,
            encontrar_com as visitando,
            empresa,
            tipo_visita,
            data_entrada,
            status,
            foto_caminho as foto
        FROM visitorinfo 
        WHERE status = 1  
        ORDER BY data_entrada DESC, nome_completo ASC
    ");

    if ($result['status'] !== 'success' || empty($result['data'])) {
        apiJsonResponse([]);
    }

    $visitantes = array_map(static fn($item) => (array) $item, $result['data']);
    apiJsonResponse($visitantes);
} catch (Exception $e) {
    logApiException($e);
    apiJsonResponse([], 200);
}
