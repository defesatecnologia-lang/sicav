<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';

apiRequireEndpointAccess();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    apiError('ID nao fornecido', 400);
}

try {
    $db = new database();
    $result = $db->select(
        "SELECT a.*, v.nome_completo as visitante_nome, v.numero_cpf as visitante_cpf,
                u.nome as usuario_nome, u.usuario as usuario_login
         FROM auditoria_visitantes a
         LEFT JOIN visitorinfo v ON a.registro_id = v.id
         LEFT JOIN usuarios u ON a.usuario_id = u.id
         WHERE a.id = ?",
        [$id]
    );

    if ($result['success'] !== true || empty($result['data'])) {
        apiError('Registro nao encontrado', 404);
    }

    $dados = $result['data'][0];
    if (!empty($dados->dados_antigos)) {
        $dados->dados_antigos_decoded = json_decode($dados->dados_antigos);
    }
    if (!empty($dados->dados_novos)) {
        $dados->dados_novos_decoded = json_decode($dados->dados_novos);
    }

    apiJsonResponse((array) $dados);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro interno', 500);
}
