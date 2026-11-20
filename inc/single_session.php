<?php

require_once __DIR__ . '/../config/database.php';

function getSessionClientIp(): string
{
    return getClientIp();
}

function getSessionUserAgent(): string
{
    $ua = trim($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    return mb_substr($ua, 0, 255);
}

function hashSessionToken(string $token): string
{
    return hash('sha256', $token);
}

function ensureUserSessionsTable(database $db): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $result = $db->select("SELECT 1 FROM user_sessions LIMIT 1");
    if (($result['status'] ?? '') !== 'success') {
        throw new RuntimeException('Tabela user_sessions indisponivel. Execute a migracao de banco.');
    }

    $checked = true;
}

function createSessionToken(): string
{
    return bin2hex(random_bytes(32));
}

function registerUniqueSession(database $db, int $usuarioId, string $token): void
{
    ensureUserSessionsTable($db);

    $db->insert(
        "INSERT INTO user_sessions (usuario_id, session_token_hash, session_id, ip_address, user_agent, active, last_seen, expires_at, last_activity_at)
         VALUES (:usuario_id, :session_token_hash, :session_id, :ip_address, :user_agent, 1, NOW(), DATE_ADD(NOW(), INTERVAL :expires SECOND), NOW())
         ON DUPLICATE KEY UPDATE
             session_token_hash = VALUES(session_token_hash),
             session_id = VALUES(session_id),
             ip_address = VALUES(ip_address),
             user_agent = VALUES(user_agent),
             active = 1,
             revoked_at = NULL,
             last_seen = NOW(),
             expires_at = DATE_ADD(NOW(), INTERVAL :expires SECOND),
             last_activity_at = NOW(),
             updated_at = CURRENT_TIMESTAMP",
        [
            ':usuario_id' => $usuarioId,
            ':session_token_hash' => hashSessionToken($token),
            ':session_id' => session_id(),
            ':ip_address' => getSessionClientIp(),
            ':user_agent' => getSessionUserAgent(),
            ':expires' => (int) SESSION_ABSOLUTE_LIFETIME,
        ]
    );
}

function invalidateUniqueSession(database $db, int $usuarioId, ?string $token = null): void
{
    ensureUserSessionsTable($db);
    if ($token) {
        $db->update(
            "UPDATE user_sessions
             SET active = 0, revoked_at = NOW(), updated_at = CURRENT_TIMESTAMP
             WHERE usuario_id = :usuario_id
               AND session_token_hash = :session_token_hash",
            [
                ':usuario_id' => $usuarioId,
                ':session_token_hash' => hashSessionToken($token),
            ]
        );
        return;
    }

    $db->update(
        "UPDATE user_sessions
         SET active = 0, revoked_at = NOW(), updated_at = CURRENT_TIMESTAMP
         WHERE usuario_id = :usuario_id",
        [':usuario_id' => $usuarioId]
    );
}

function isUniqueSessionValid(database $db, int $usuarioId, ?string $token): bool
{
    if (!$token) {
        return false;
    }

    ensureUserSessionsTable($db);
    $result = $db->select(
        "SELECT session_token_hash, session_id, active, expires_at, revoked_at
         FROM user_sessions
         WHERE usuario_id = :usuario_id
         LIMIT 1",
        [':usuario_id' => $usuarioId]
    );

    if (($result['status'] ?? '') !== 'success' || empty($result['data'])) {
        return false;
    }

    $row = $result['data'][0];
    if ((int)($row->active ?? 0) !== 1) {
        return false;
    }

    if (!empty($row->revoked_at)) {
        return false;
    }

    if (!empty($row->expires_at)) {
        $expiresTs = strtotime((string) $row->expires_at);
        if ($expiresTs !== false && $expiresTs < time()) {
            return false;
        }
    }

    if (!hash_equals((string)$row->session_token_hash, hashSessionToken($token))) {
        return false;
    }

    $db->update(
        "UPDATE user_sessions
         SET session_id = :session_id,
             ip_address = :ip_address,
             user_agent = :user_agent,
             last_seen = NOW(),
             last_activity_at = NOW(),
             updated_at = CURRENT_TIMESTAMP
         WHERE usuario_id = :usuario_id
           AND session_token_hash = :session_token_hash
           AND active = 1
           AND (revoked_at IS NULL)
           AND (expires_at IS NULL OR expires_at > NOW())",
        [
            ':session_id' => session_id(),
            ':ip_address' => getSessionClientIp(),
            ':user_agent' => getSessionUserAgent(),
            ':usuario_id' => $usuarioId,
            ':session_token_hash' => hashSessionToken($token),
        ]
    );

    return true;
}

function purgeExpiredSessions(database $db): void
{
    $db->delete(
        "DELETE FROM user_sessions
         WHERE active = 1
           AND (expires_at IS NOT NULL AND expires_at < NOW())"
    );
}

function finalizeLoginSession(database $db, object $usuario, ?callable $onError = null): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario;
    unset($_SESSION['usuario']->senha); // nao armazenar hash na sessao
    $_SESSION['ultimo_acesso'] = time();
    $_SESSION['session_token'] = createSessionToken();
    try {
        registerUniqueSession($db, (int)$usuario->id, $_SESSION['session_token']);
    } catch (Throwable $e) {
        error_log('Falha ao registrar sessao unica: ' . $e->getMessage());
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        if ($onError) {
            $onError();
        }
        setLoginErrorAndRedirect('Falha de configuracao de seguranca. Contate o administrador.');
    }
    if (function_exists('logEvent')) {
        logEvent('SESSION_CREATED', ['user_id' => (int)$usuario->id]);
    }
}

function setLoginErrorAndRedirect(string $mensagem): void
{
    $_SESSION['error'] = $mensagem;
    header('Location: index.php?page=login');
    exit;
}

function clearLocalSessionState(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
