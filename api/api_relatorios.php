<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('supervisor');

try {
    $db = new database();
    $query = "SELECT * FROM visitorinfo WHERE 1=1";
    $params = [];

    if (!empty($_GET['data_inicial'])) {
        $dataInicial = DateTime::createFromFormat('d/m/Y', trim($_GET['data_inicial']));
        if ($dataInicial) {
            $horaInicial = !empty($_GET['hora_inicial']) ? $_GET['hora_inicial'] . ':00' : '00:00:00';
            $query .= " AND data_entrada >= :data_inicial";
            $params[':data_inicial'] = $dataInicial->format('Y-m-d') . ' ' . $horaInicial;
        }
    }

    if (!empty($_GET['data_final'])) {
        $dataFinal = DateTime::createFromFormat('d/m/Y', trim($_GET['data_final']));
        if ($dataFinal) {
            $horaFinal = !empty($_GET['hora_final']) ? $_GET['hora_final'] . ':00' : '23:59:59';
            $query .= " AND data_entrada <= :data_final";
            $params[':data_final'] = $dataFinal->format('Y-m-d') . ' ' . $horaFinal;
        }
    }

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $query .= " AND status = :status";
        $params[':status'] = (int) $_GET['status'];
    }

    if (!empty($_GET['tipo_visita'])) {
        $query .= " AND tipo_visita = :tipo_visita";
        $params[':tipo_visita'] = (string) $_GET['tipo_visita'];
    }

    if (!empty($_GET['pesquisa'])) {
        $params[':pesquisa'] = '%' . trim((string) $_GET['pesquisa']) . '%';
        $query .= "
            AND (nome_completo LIKE :pesquisa
                OR numero_cpf LIKE :pesquisa
                OR veiculo_placa LIKE :pesquisa
                OR encontrar_com LIKE :pesquisa
                OR empresa LIKE :pesquisa
                OR telefone LIKE :pesquisa
                OR email LIKE :pesquisa)";
    }

    $query .= " ORDER BY data_entrada DESC";
    $result = $db->query($query, $params);
    if ($result['success'] !== true) {
        throw new Exception('Erro ao consultar dados');
    }

    $dados = array_map(static function ($item) {
        $row = (array) $item;
        $row['foto'] = !empty($row['foto_caminho']) ? $row['foto_caminho'] : null;
        return $row;
    }, $result['data']);

    apiJsonResponse([
        'success' => true,
        'dados' => $dados,
        'total' => count($dados),
    ]);
} catch (Exception $e) {
    logApiException($e);
    apiError('Erro ao consultar relatorio', 500);
}
