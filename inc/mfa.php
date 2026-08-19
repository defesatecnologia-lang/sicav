<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/log_event.php';

function mfaGetForUser(database $db, int $usuarioId): ?object
{
    $result = $db->select(
        "SELECT id, usuario_id, secret, enabled, created_at, updated_at
         FROM usuarios_mfa
         WHERE usuario_id = :usuario_id
         LIMIT 1",
        [':usuario_id' => $usuarioId]
    );

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return null;
    }

    return $result['data'][0];
}

function mfaIsEnabled(database $db, int $usuarioId): bool
{
    $row = mfaGetForUser($db, $usuarioId);
    return $row !== null && (int) ($row->enabled ?? 0) === 1;
}

function mfaCreatePending(database $db, int $usuarioId, string $secret): void
{
    $encrypted = mfaEncryptSecret($secret);
    $db->insert(
        "INSERT INTO usuarios_mfa (usuario_id, secret, enabled, created_at, updated_at)
         VALUES (:usuario_id, :secret, 0, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             secret = VALUES(secret),
             enabled = 0,
             updated_at = NOW()",
        [
            ':usuario_id' => $usuarioId,
            ':secret' => $encrypted,
        ]
    );
}

function mfaEnable(database $db, int $usuarioId): void
{
    $db->update(
        "UPDATE usuarios_mfa SET enabled = 1, updated_at = NOW() WHERE usuario_id = :usuario_id",
        [':usuario_id' => $usuarioId]
    );
}

function mfaDisable(database $db, int $usuarioId): void
{
    $db->update(
        "UPDATE usuarios_mfa SET enabled = 0, updated_at = NOW() WHERE usuario_id = :usuario_id",
        [':usuario_id' => $usuarioId]
    );
}

function mfaStoreRecoveryCodes(database $db, int $usuarioId, array $plainCodes): void
{
    $db->delete(
        "DELETE FROM mfa_recovery_codes WHERE usuario_id = :usuario_id",
        [':usuario_id' => $usuarioId]
    );

    foreach ($plainCodes as $code) {
        $db->insert(
            "INSERT INTO mfa_recovery_codes (usuario_id, code_hash, created_at)
             VALUES (:usuario_id, :code_hash, NOW())",
            [
                ':usuario_id' => $usuarioId,
                ':code_hash' => mfaHashRecoveryCode($code),
            ]
        );
    }
}

function mfaUseRecoveryCode(database $db, int $usuarioId, string $code): bool
{
    $hash = mfaHashRecoveryCode(strtoupper(trim($code)));

    $result = $db->select(
        "SELECT id FROM mfa_recovery_codes
         WHERE usuario_id = :usuario_id AND code_hash = :code_hash AND used = 0
         LIMIT 1",
        [
            ':usuario_id' => $usuarioId,
            ':code_hash' => $hash,
        ]
    );

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return false;
    }

    $db->update(
        "UPDATE mfa_recovery_codes SET used = 1, used_at = NOW()
         WHERE usuario_id = :usuario_id AND code_hash = :code_hash",
        [
            ':usuario_id' => $usuarioId,
            ':code_hash' => $hash,
        ]
    );

    return true;
}

function mfaRemainingRecoveryCodes(database $db, int $usuarioId): int
{
    $result = $db->select(
        "SELECT COUNT(*) AS total FROM mfa_recovery_codes
         WHERE usuario_id = :usuario_id AND used = 0",
        [':usuario_id' => $usuarioId]
    );

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return 0;
    }

    return (int) ($result['data'][0]->total ?? 0);
}

function mfaVerify(database $db, int $usuarioId, string $code): bool
{
    $row = mfaGetForUser($db, $usuarioId);
    if ($row === null || (int) ($row->enabled ?? 0) !== 1) {
        return false;
    }

    try {
        $secret = mfaDecryptSecret((string) $row->secret);
    } catch (Throwable $e) {
        error_log('Erro ao decriptar secret MFA: ' . $e->getMessage());
        return false;
    }

    return mfaVerifyCode($secret, $code);
}