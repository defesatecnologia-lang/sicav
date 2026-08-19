<?php

require_once __DIR__ . '/security.php';

function getRequestId(): string
{
    static $requestId = null;
    if ($requestId === null) {
        $requestId = bin2hex(random_bytes(16));
    }
    return $requestId;
}

function logEvent(string $event, array $context = []): void
{
    $userLevel = isset($_SESSION['usuario']) ? ($_SESSION['usuario']->nivel_acesso ?? null) : null;

    $payload = array_merge([
        'event' => $event,
        'request_id' => getRequestId(),
        'timestamp' => gmdate('c'),
        'user_id' => currentUserId(),
        'user_level' => $userLevel,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'route' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    ], $context);

    error_log('AUDIT ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}