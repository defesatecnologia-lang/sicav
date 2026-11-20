<?php

const MAX_LOGIN_ATTEMPTS = 5;
const MAX_IP_LOGIN_ATTEMPTS = 15;
const LOGIN_LOCK_SECONDS = 900; // 15 minutos

function getLoginAttempt(database $db, string $usuario, string $ip): array
{
    $sql = "SELECT attempt_count, lock_until
            FROM login_attempts
            WHERE usuario = :usuario AND ip_address = :ip
            LIMIT 1";
    $result = $db->select($sql, [':usuario' => $usuario, ':ip' => $ip]);

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return ['attempt_count' => 0, 'lock_until_ts' => 0];
    }

    $row = $result['data'][0];
    $lockUntilTs = 0;
    if (!empty($row->lock_until)) {
        $parsed = strtotime((string)$row->lock_until);
        $lockUntilTs = $parsed !== false ? $parsed : 0;
    }

    return [
        'attempt_count' => (int)($row->attempt_count ?? 0),
        'lock_until_ts' => $lockUntilTs
    ];
}

function registerLoginFailure(database $db, string $usuario, string $ip, int $currentCount, int $maxAttempts): array
{
    $newCount = $currentCount + 1;
    $shouldLock = $newCount >= $maxAttempts;
    $lockUntil = $shouldLock ? date('Y-m-d H:i:s', time() + LOGIN_LOCK_SECONDS) : null;

    $sql = "INSERT INTO login_attempts (usuario, ip_address, attempt_count, lock_until)
            VALUES (:usuario, :ip, :attempt_count, :lock_until)
            ON DUPLICATE KEY UPDATE
                attempt_count = VALUES(attempt_count),
                lock_until = VALUES(lock_until),
                updated_at = CURRENT_TIMESTAMP";

    $db->insert($sql, [
        ':usuario' => $usuario,
        ':ip' => $ip,
        ':attempt_count' => $newCount,
        ':lock_until' => $lockUntil
    ]);

    return ['attempt_count' => $newCount, 'locked' => $shouldLock];
}

function clearLoginAttempts(database $db, string $usuario, string $ip): void
{
    $sql = "DELETE FROM login_attempts WHERE usuario = :usuario AND ip_address = :ip";
    $db->delete($sql, [':usuario' => $usuario, ':ip' => $ip]);
}

function cleanupOldLoginAttempts(database $db): void
{
    $sql = "DELETE FROM login_attempts
            WHERE (lock_until IS NOT NULL AND lock_until < NOW())
               OR updated_at < (NOW() - INTERVAL 7 DAY)";
    $db->delete($sql);
}