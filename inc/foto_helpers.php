<?php

/**
 * Helpers de upload de fotos de visitantes
 *
 * Uso:
 *   require_once __DIR__ . '/foto_helpers.php';
 *   $fotoPath = salvarFotoBase64($data->foto, FOTOS_DIR);
 */

if (!defined('MAX_IMAGE_BYTES')) {
    define('MAX_IMAGE_BYTES', 5 * 1024 * 1024);
}

function salvarFotoBase64(string $imageData, string $fotosDir): ?string
{
    if ($imageData === '') {
        return null;
    }

    if (!preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
        throw new InvalidArgumentException('Formato de imagem base64 invalido');
    }

    $decodedInput = substr($imageData, strpos($imageData, ',') + 1);
    $type = strtolower($type[1]);
    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'], true)) {
        throw new InvalidArgumentException("Tipo de imagem nao suportado: {$type}");
    }

    $decoded = base64_decode($decodedInput, true);
    if ($decoded === false) {
        throw new InvalidArgumentException('Falha ao decodificar imagem');
    }
    if (strlen($decoded) > MAX_IMAGE_BYTES) {
        throw new InvalidArgumentException('Imagem excede o tamanho maximo de 5MB');
    }

    $imgInfo = @getimagesizefromstring($decoded);
    if ($imgInfo === false || empty($imgInfo['mime'])) {
        throw new InvalidArgumentException('Conteudo de imagem invalido');
    }
    $mimeToExt = [
        'image/jpeg' => 'jpeg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];
    $realExt = $mimeToExt[strtolower((string) $imgInfo['mime'])] ?? null;
    if ($realExt === null) {
        throw new InvalidArgumentException('Tipo MIME de imagem nao suportado');
    }
    if ($realExt !== $type && !($realExt === 'jpeg' && $type === 'jpg')) {
        throw new InvalidArgumentException('Extensao de imagem nao confere com o conteudo');
    }

    $nomeArquivo = 'visitante_' . uniqid('', true) . '_' . time() . '.' . $type;
    $caminhoCompleto = $fotosDir . $nomeArquivo;
    if (file_put_contents($caminhoCompleto, $decoded) === false) {
        throw new InvalidArgumentException('Falha ao salvar arquivo de imagem');
    }

    return 'fotos_visitantes/' . $nomeArquivo;
}