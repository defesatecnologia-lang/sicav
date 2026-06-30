<?php
require_once __DIR__ . '/../inc/api_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Metodo nao permitido', 405);
}

apiRequireEndpointAccess();
apiRequireCsrf();

$input = apiGetJsonInput();
$visitorId = $input['vid'] ?? 0;

if (!$visitorId || !is_numeric($visitorId)) {
    apiError('ID invalido', 400);
}

try {
    $db = new database();
    $db->setUsuarioAuditoria((int) currentUserId());

    $checkSql = "SELECT id, status FROM visitorinfo WHERE id = :id";
    $checkResult = $db->select($checkSql, [':id' => (int) $visitorId]);

    if ($checkResult['status'] !== 'success' || count($checkResult['data']) === 0) {
        apiError('Visitante nao encontrado', 404);
    }

    if ((int) $checkResult['data'][0]->status === 0) {
        apiError('Visitante ja finalizado', 409);
    }

    $result = $db->update(
        "UPDATE visitorinfo SET status = 0, data_saida = NOW() WHERE id = :id",
        [':id' => (int) $visitorId]
    );

    if ($result['status'] !== 'success') {
        apiError('Erro ao registrar saida', 500);
    }

    apiSuccess(null, 'Saida registrada com sucesso');
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro interno', 500);
}
