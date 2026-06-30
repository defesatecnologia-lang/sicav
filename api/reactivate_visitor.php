<?php
require_once __DIR__ . '/../inc/api_bootstrap.php';

apiRequireEndpointAccess();

try {
    $db = new database();
    $termo = trim($_GET['q'] ?? '');

    $sql = "
        SELECT 
            v1.id,
            v1.nome_completo as nome,
            v1.numero_cpf as cpf,
            v1.veiculo_placa as placa,
            v1.telefone,
            v1.email,
            v1.empresa,
            v1.data_entrada,
            v1.foto_caminho as foto
        FROM visitorinfo v1
        WHERE v1.status = 0
        AND v1.id = (
            SELECT MAX(v2.id)
            FROM visitorinfo v2
            WHERE v2.numero_cpf = v1.numero_cpf
                AND v2.status = 0
        )
        AND NOT EXISTS (
            SELECT 1
            FROM visitorinfo v3
            WHERE v3.numero_cpf = v1.numero_cpf
                AND v3.status = 1
        )
    ";

    $params = [];
    if ($termo !== '' && strlen($termo) >= 2) {
        $sql .= " AND (
            v1.nome_completo LIKE :termo OR
            v1.numero_cpf LIKE :termo OR
            v1.veiculo_placa LIKE :termo
        )";
        $params[':termo'] = "%{$termo}%";
    }

    $sql .= " ORDER BY v1.data_entrada DESC";
    $result = $db->select($sql, $params);

    if ($result['success'] !== true) {
        apiError('Erro ao buscar cadastros inativos', 500);
    }

    $visitantes = array_map(static fn($item) => (array) $item, $result['data']);
    apiJsonResponse($visitantes);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro ao buscar cadastros inativos', 500);
}
