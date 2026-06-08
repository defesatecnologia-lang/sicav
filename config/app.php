<?php

function appEnv(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', (int) appEnv('VISITORPASS_SESSION_TIMEOUT', '3600'));
}
if (!defined('APP_NAME')) {
    define('APP_NAME', appEnv('VISITORPASS_APP_NAME', 'Sistema de Visitantes'));
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', appEnv('VISITORPASS_ENV', 'production'));
}

function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

function configureSessionSecurity(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $secureCookie = isHttpsRequest();
    ini_set('session.cookie_secure', $secureCookie ? '1' : '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
