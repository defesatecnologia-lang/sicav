<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/validators.php';

define('FOTOS_DIR', __DIR__ . '/../fotos_visitantes/');
define('MAX_IMAGE_BYTES', 5 * 1024 * 1024);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Metodo invalido', 405);
}

apiRequireAuth('user');
apiRequireCsrf();

if (!is_dir(FOTOS_DIR) && !mkdir(FOTOS_DIR, 0755, true) && !is_dir(FOTOS_DIR)) {
    apiError('Falha ao criar diretorio de fotos', 500);
}

try {
    $db = new database();
    $usuarioId = currentUserId();
    if (!$usuarioId) {
        throw new Exception('Usuario nao autenticado');
    }
    $db->setUsuarioAuditoria($usuarioId);

    $jsonData = apiGetJsonInput();
    $data = !empty($_POST) ? (object) $_POST : (object) $jsonData;
    if (empty((array) $data)) {
        throw new Exception('Dados nao recebidos');
    }

    $required = ['uname', 'cpf', 'meet', 'representing'];
    foreach ($required as $field) {
        if (empty($data->$field)) {
            throw new Exception("Campo obrigatorio faltando: {$field}");
        }
    }

    // Validar nome
    if (!validateNome($data->uname)) {
        throw new Exception('Nome invalido: deve ter entre 3 e 255 caracteres');
    }

    // Validar CPF
    if (!validateCPF($data->cpf)) {
        throw new Exception('CPF invalido: verifique o formato e os dígitos verificadores');
    }

    // Validar email se fornecido
    if (!empty($data->email) && !validateEmail($data->email)) {
        throw new Exception('Email invalido: verifique o formato');
    }

    // Validar telefone se fornecido
    if (!empty($data->mobileno) && !validateTelefone($data->mobileno)) {
        throw new Exception('Telefone invalido: use formato brasileiro (11 dígitos com DDD)');
    }

    // Validar placa se fornecida
    if (!empty($data->placa) && !validatePlaca($data->placa)) {
        throw new Exception('Placa invalida: use formato padrão (ABC-1234) ou Mercosul (ABC1D23)');
    }

    $fotoPath = null;
    if (!empty($data->foto)) {
        $imageData = $data->foto;
        if (!preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            throw new Exception('Formato de imagem base64 invalido');
        }

        $imageData = substr($imageData, strpos($imageData, ',') + 1);
        $type = strtolower($type[1]);
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            throw new Exception("Tipo de imagem nao suportado: {$type}");
        }

        $decoded = base64_decode($imageData, true);
        if ($decoded === false) {
            throw new Exception('Falha ao decodificar imagem');
        }
        if (strlen($decoded) > MAX_IMAGE_BYTES) {
            throw new Exception('Imagem excede o tamanho maximo de 5MB');
        }

        $imgInfo = @getimagesizefromstring($decoded);
        if ($imgInfo === false || empty($imgInfo['mime'])) {
            throw new Exception('Conteudo de imagem invalido');
        }
        $mimeToExt = [
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        $realExt = $mimeToExt[strtolower((string)$imgInfo['mime'])] ?? null;
        if ($realExt === null) {
            throw new Exception('Tipo MIME de imagem nao suportado');
        }
        if ($realExt !== $type && !($realExt === 'jpeg' && $type === 'jpg')) {
            throw new Exception('Extensao de imagem nao confere com o conteudo');
        }

        $nomeArquivo = 'visitante_' . uniqid('', true) . '_' . time() . '.' . $type;
        $caminhoCompleto = FOTOS_DIR . $nomeArquivo;
        if (file_put_contents($caminhoCompleto, $decoded) === false) {
            throw new Exception('Falha ao salvar arquivo de imagem');
        }
        $fotoPath = 'fotos_visitantes/' . $nomeArquivo;
    }

    $cpf = preg_replace('/\D/', '', (string) $data->cpf);
    $placa = !empty($data->placa) ? preg_replace('/[^A-Za-z0-9]/', '', strtoupper((string) $data->placa)) : null;

    $resultCheck = $db->query(
        "SELECT id, nome_completo FROM visitorinfo WHERE numero_cpf = ? AND status = 1 ORDER BY data_entrada DESC LIMIT 1",
        [$cpf]
    );
    if ($resultCheck['status'] === 'success' && !empty($resultCheck['data'])) {
        $visitanteAtivo = $resultCheck['data'][0];
        throw new Exception("Ja existe um visitante ativo com este CPF: {$visitanteAtivo->nome_completo} (ID: {$visitanteAtivo->id})");
    }

    if ($placa) {
        $resultCheckPlaca = $db->query(
            "SELECT id, nome_completo FROM visitorinfo WHERE veiculo_placa = ? AND status = 1 ORDER BY data_entrada DESC LIMIT 1",
            [$placa]
        );
        if ($resultCheckPlaca['status'] === 'success' && !empty($resultCheckPlaca['data'])) {
            $visitanteAtivo = $resultCheckPlaca['data'][0];
            throw new Exception("Ja existe um visitante ativo para esta placa: {$visitanteAtivo->nome_completo} (ID: {$visitanteAtivo->id})");
        }
    }

    $sql = "INSERT INTO visitorinfo 
            (nome_completo, numero_cpf, veiculo_placa, telefone, email, empresa, 
            data_entrada, status, foto_caminho, encontrar_com, tipo_visita, cadastro_original_id) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, ?, ?, ?, ?)";

    $params = [
        $data->uname,
        $cpf,
        $placa,
        $data->mobileno ?? null,
        $data->email ?? null,
        $data->from ?? null,
        $fotoPath,
        $data->meet,
        $data->representing,
        $data->original_id ?? null,
    ];

    $result = $db->query($sql, $params);
    if ($result['status'] !== 'success') {
        throw new Exception('Erro ao inserir no banco de dados');
    }

    apiJsonResponse([
        'success' => true,
        'id' => $result['insert_id'],
        'message' => 'Visitante cadastrado com sucesso!',
        'foto_salva' => $fotoPath !== null,
    ]);
} catch (Exception $e) {
    $safeMessages = [
        'Usuario nao autenticado',
        'Dados nao recebidos',
        'Formato de imagem base64 invalido',
        'Falha ao decodificar imagem',
        'Imagem excede o tamanho maximo de 5MB',
        'Conteudo de imagem invalido',
        'Tipo MIME de imagem nao suportado',
        'Extensao de imagem nao confere com o conteudo',
        'Falha ao salvar arquivo de imagem',
        'Erro ao inserir no banco de dados',
    ];
    $msg = $e->getMessage();
    $safe = in_array($msg, $safeMessages, true)
        || str_starts_with($msg, 'Campo obrigatorio faltando:')
        || str_starts_with($msg, 'Ja existe um visitante ativo')
        || str_starts_with($msg, 'Nome invalido:')
        || str_starts_with($msg, 'CPF invalido:')
        || str_starts_with($msg, 'Email invalido:')
        || str_starts_with($msg, 'Telefone invalido:')
        || str_starts_with($msg, 'Placa invalida:');

    if (!$safe) {
        logApiException($e);
        $msg = 'Erro interno ao processar cadastro';
    }

    apiJsonResponse(['success' => false, 'error' => $msg], $safe ? 400 : 500);
}
