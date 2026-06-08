<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('admin');

function sanitizeSpreadsheetCell($value): string
{
    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return '';
    }

    $first = substr($text, 0, 1);
    if (in_array($first, ['=', '+', '-', '@'], true)) {
        return "'" . $text;
    }

    return $text;
}

$formato = $_GET['formato'] ?? 'csv';
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

try {
    $db = new database();
    $sql = "
        SELECT a.data_acao, a.acao, u.nome as usuario, u.usuario as login_usuario,
               v.nome_completo as visitante, v.numero_cpf as cpf, a.dados_antigos, a.dados_novos
        FROM auditoria_visitantes a
        LEFT JOIN visitorinfo v ON a.registro_id = v.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE DATE(a.data_acao) BETWEEN ? AND ?
        ORDER BY a.data_acao DESC
    ";
    $result = $db->query($sql, [$dataInicio, $dataFim]);
    $rows = ($result['status'] === 'success') ? $result['data'] : [];

    if ($formato === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=auditoria_' . date('Y-m-d') . '.json');
        echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=auditoria_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Data', 'Acao', 'Usuario', 'Visitante', 'CPF', 'Dados Antigos', 'Dados Novos']);
    foreach ($rows as $row) {
        fputcsv($output, [
            sanitizeSpreadsheetCell($row->data_acao ?? ''),
            sanitizeSpreadsheetCell($row->acao ?? ''),
            sanitizeSpreadsheetCell($row->usuario ?? ''),
            sanitizeSpreadsheetCell($row->visitante ?? ''),
            sanitizeSpreadsheetCell($row->cpf ?? ''),
            sanitizeSpreadsheetCell($row->dados_antigos ?? ''),
            sanitizeSpreadsheetCell($row->dados_novos ?? ''),
        ]);
    }
    fclose($output);
    exit;
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro ao exportar auditoria', 500);
}
