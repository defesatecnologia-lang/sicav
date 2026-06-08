<?php

require_once __DIR__ . '/../inc/api_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

apiRequireAuth('user');

$visitorId = isset($_GET['vid']) ? (int) $_GET['vid'] : 0;
if ($visitorId <= 0) {
    serveDefaultAvatar();
    exit;
}

try {
    $db = new database();
    $result = $db->query("SELECT foto_caminho FROM visitorinfo WHERE id = :id", [':id' => $visitorId]);

    if ($result['status'] === 'success' && count($result['data']) > 0) {
        $foto = $result['data'][0]->foto_caminho ?? null;
        if ($foto) {
            $fotoPath = __DIR__ . '/../' . $foto;
            if (file_exists($fotoPath) && isValidImage($fotoPath)) {
                $ext = strtolower((string) pathinfo($fotoPath, PATHINFO_EXTENSION));
                header('Content-Type: ' . mapImageMimeByExtension($ext));
                readfile($fotoPath);
                exit;
            }
        }
    }
    serveDefaultAvatar();
} catch (Exception $e) {
    logApiException($e);
    serveDefaultAvatar();
}

function mapImageMimeByExtension(string $ext): string
{
    return match ($ext) {
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/jpeg',
    };
}

function isValidImage(string $path): bool
{
    $imgInfo = @getimagesize($path);
    return is_array($imgInfo) && !empty($imgInfo['mime']);
}

function serveDefaultAvatar(): void
{
    $avatarPath = __DIR__ . '/../public/imagens/avatar.png';
    if (is_file($avatarPath) && isValidImage($avatarPath)) {
        header('Content-Type: image/png');
        readfile($avatarPath);
        return;
    }

    // Fallback sem dependencia de GD: 1x1 PNG transparente.
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl9xwAAAABJRU5ErkJggg==');
}
