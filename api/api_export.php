<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';

apiRequireEndpointAccess();

if (!isset($_GET['exportar']) || $_GET['exportar'] !== 'excel') {
    apiError('Parametro invalido', 400);
}

try {
    $db = new database();
    $result = $db->select("SELECT * FROM visitorinfo ORDER BY data_entrada DESC");

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="relatorio_visitantes_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    echo "ID\tNome\tCPF\tPlaca\tEncontrar Com\tEmpresa\tTipo Visita\tTelefone\tE-mail\tData Entrada\tData Saida\tStatus\tFoto\n";

    if ($result['status'] === 'success') {
        foreach ($result['data'] as $linhaObj) {
            $linha = (array) $linhaObj;
            $foto = !empty($linha['foto_caminho']) ? sanitizeSpreadsheetCell($linha['foto_caminho']) : "";
            echo sanitizeSpreadsheetCell($linha['id'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['nome_completo'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['numero_cpf'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['veiculo_placa'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['encontrar_com'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['empresa'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['tipo_visita'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['telefone'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['email'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['data_entrada'] ?? '') . "\t";
            echo sanitizeSpreadsheetCell($linha['data_saida'] ?? '') . "\t";
            echo ((int) $linha['status'] === 1 ? 'Ativo' : 'Finalizado') . "\t";
            echo $foto . "\n";
        }
    }
    exit;
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro ao exportar', 500);
}
