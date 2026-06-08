<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('supervisor');

try {
    $db = new database();
    $total = $db->query("SELECT COUNT(*) as total FROM visitorinfo")['data'][0]->total ?? 0;
    $ativos = $db->query("SELECT COUNT(*) as ativos FROM visitorinfo WHERE status = 1")['data'][0]->ativos ?? 0;
    $finalizados = $db->query("SELECT COUNT(*) as finalizados FROM visitorinfo WHERE status = 0")['data'][0]->finalizados ?? 0;
    $hoje = $db->query("SELECT COUNT(*) as hoje FROM visitorinfo WHERE DATE(data_entrada) = CURDATE()")['data'][0]->hoje ?? 0;

    apiJsonResponse([
        'success' => true,
        'total' => (int) $total,
        'ativos' => (int) $ativos,
        'finalizados' => (int) $finalizados,
        'hoje' => (int) $hoje,
    ]);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro interno', 500);
}
