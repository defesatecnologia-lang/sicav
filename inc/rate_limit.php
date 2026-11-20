<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

function checkRateLimit(database $db, string $route, int $maxRequests, int $windowSeconds, ?string $ip = null): bool
{
    $ip = $ip ?: getClientIp();
    $rateKey = sha1($route . '|' . $ip);

    $now = time();
    $windowStart = $now - $windowSeconds;

    $result = $db->select(
        "SELECT request_count, window_started_at
         FROM rate_limits
         WHERE rate_key = :rate_key
         LIMIT 1",
        [':rate_key' => $rateKey]
    );

    $count = 0;
    $startedAt = $now;
    if (($result['status'] ?? '') === 'success' && !empty($result['data'])) {
        $row = $result['data'][0];
        $parsed = strtotime((string) ($row->window_started_at ?? ''));
        $startedAt = ($parsed !== false) ? $parsed : $now;

        if ($startedAt <= $windowStart) {
            $count = 0;
            $startedAt = $now;
        } else {
            $count = (int) ($row->request_count ?? 0);
        }
    }

    $count++;

    $db->insert(
        "INSERT INTO rate_limits (rate_key, request_count, window_started_at)
         VALUES (:rate_key, :count, FROM_UNIXTIME(:window_started))
         ON DUPLICATE KEY UPDATE
             request_count = :count,
             window_started_at = FROM_UNIXTIME(:window_started),
             updated_at = CURRENT_TIMESTAMP",
        [
            ':rate_key' => $rateKey,
            ':count' => $count,
            ':window_started' => $startedAt,
        ]
    );

    return $count <= $maxRequests;
}

function enforceRateLimit(database $db, string $route, int $maxRequests, int $windowSeconds, ?string $ip = null): void
{
    $allowed = checkRateLimit($db, $route, $maxRequests, $windowSeconds, $ip);
    if ($allowed) {
        return;
    }

    if (function_exists('logEvent')) {
        logEvent('RATE_LIMIT_TRIGGERED', [
            'route' => $route,
            'ip' => $ip ?: getClientIp(),
            'limit' => $maxRequests,
            'window' => $windowSeconds,
        ]);
    }

    if (function_exists('apiJsonResponse')) {
        apiJsonResponse([
            'success' => false,
            'error' => 'Limite de requisicoes excedido. Tente novamente em instantes.',
        ], 429);
    }

    http_response_code(429);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'Limite de requisicoes excedido. Tente novamente em instantes.',
    ]);
    exit;
}

function cleanupOldRateLimits(database $db): void
{
    $db->delete(
        "DELETE FROM rate_limits
         WHERE window_started_at < (NOW() - INTERVAL 1 DAY)"
    );
}