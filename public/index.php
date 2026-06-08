<?php
require_once __DIR__ . '/../config/app.php';
configureSessionSecurity();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/security.php';
require_once __DIR__ . '/../inc/single_session.php';
header('Content-Type: text/html; charset=UTF-8');

$tempo_maximo = SESSION_TIMEOUT; // tempo limite de inatividade em segundos
$page = $_GET['page'] ?? 'home';

// Base publica real da aplicacao:
// - DocumentRoot em /public  -> ""
// - App servida em /projeto/public -> "/projeto/public"
$publicBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($publicBasePath === '/') {
    $publicBasePath = '';
}
$GLOBALS['APP_BASE_PATH'] = $publicBasePath;
$GLOBALS['APP_PUBLIC_PATH'] = $publicBasePath;

// CSP centralizada: em produção evita unsafe-eval; em dev mantém compatibilidade.
$isProduction = defined('ENVIRONMENT') && ENVIRONMENT === 'production';
$scriptEvalPolicy = $isProduction ? "" : " 'unsafe-eval'";
$cspNonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
$GLOBALS['CSP_NONCE'] = $cspNonce;

$cspPolicy = "default-src 'self'; "
    . "script-src 'self' 'nonce-{$cspNonce}'{$scriptEvalPolicy} https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.datatables.net; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; "
    . "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; "
    . "img-src 'self' data: blob:; "
    . "font-src 'self' data: https://cdnjs.cloudflare.com; "
    . "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.datatables.net; "
    . "frame-ancestors 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self'";
header('Content-Security-Policy: ' . $cspPolicy);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
header('X-Frame-Options: SAMEORIGIN');

// Detectar se e requisicao de API
$isApi = str_starts_with($page, 'api/');

function verificarTimeout($isApi = false, $tempo_maximo = SESSION_TIMEOUT)
{
    if (!isset($_SESSION['usuario'])) {
        return 0;
    }

    if (!isset($_SESSION['ultimo_acesso'])) {
        $_SESSION['ultimo_acesso'] = time();
        return $tempo_maximo;
    }

    $inatividade = time() - $_SESSION['ultimo_acesso'];
    $time_left = $tempo_maximo - $inatividade;

    // Usar >= para expirar exatamente no limite
    if ($inatividade >= $tempo_maximo) {
        $usuarioId = currentUserId();
        $sessionToken = $_SESSION['session_token'] ?? null;
        if ($usuarioId) {
            try {
                $db = new database();
                invalidateUniqueSession($db, $usuarioId, $sessionToken);
            } catch (Throwable $e) {
                error_log('Erro ao invalidar sessao por timeout: ' . $e->getMessage());
            }
        }

        // Limpar sessao completamente
        clearLocalSessionState();

        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
            echo json_encode([
                'status' => 'expired',
                'success' => false,
                'error' => 'Sessao expirada',
                'message' => 'Sessao encerrada por inatividade'
            ]);
            exit;
        }

        header('Location: index.php?page=login&timeout=1');
        exit;
    }

    $_SESSION['ultimo_acesso'] = time();

    return max(0, $time_left);
}

function enforceUniqueSession(bool $isApi = false): void
{
    if (!isset($_SESSION['usuario'])) {
        return;
    }

    $usuarioId = currentUserId();
    $sessionToken = $_SESSION['session_token'] ?? null;
    if (!$usuarioId || !$sessionToken) {
        clearLocalSessionState();
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'status' => 'expired',
                'reason' => 'concurrent_login',
                'message' => 'Sessao invalida. Realize login novamente.'
            ]);
            exit;
        }

        header('Location: index.php?page=login&concurrent=1');
        exit;
    }

    try {
        $db = new database();
        $valid = isUniqueSessionValid($db, $usuarioId, $sessionToken);
    } catch (Throwable $e) {
        error_log('Erro ao validar sessao unica: ' . $e->getMessage());
        $valid = false;
    }

    if ($valid) {
        return;
    }

    clearLocalSessionState();

    if ($isApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'status' => 'expired',
            'reason' => 'concurrent_login',
            'message' => 'Sua conta foi acessada em outro dispositivo.'
        ]);
        exit;
    }

    header('Location: index.php?page=login&concurrent=1');
    exit;
}

// Processar logout antes do timeout para sempre encerrar explicitamente
if ($page === 'logout') {
    require_once __DIR__ . '/../scripts/logout.php';
    exit;
}

// Verificar timeout uma unica vez (antes de qualquer outra logica)
$time_left = verificarTimeout($isApi, $tempo_maximo);
enforceUniqueSession($isApi);
getCsrfToken();

require_once __DIR__ . '/../config/config.php';
$pages_permitidas = require_once __DIR__ . '/../inc/pages.php';
require_once __DIR__ . '/../inc/check_access.php';

// Se nao estiver logado e nao for pagina de login_submit ou API, redireciona para login
if (!isset($_SESSION['usuario']) && $page !== 'login_submit' && !str_starts_with($page, 'api/')) {
    $page = 'login';
}

// Se esta logado e tenta acessar login, vai para home
if (isset($_SESSION['usuario']) && $page === 'login') {
    $page = 'home';
}

// Verifica se a pagina existe
if (!in_array($page, $pages_permitidas, true)) {
    $page = '404';
}

// Processamento de API
if (str_starts_with($page, 'api/')) {
    if (!isset($_SESSION['usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Sessao nao encontrada']);
        exit;
    }

    $api_file = str_replace('api/', '', $page);
    $script_path = __DIR__ . '/../api/' . $api_file . '.php';

    if (!file_exists($script_path)) {
        http_response_code(404);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'API endpoint not found: ' . $api_file]);
        exit;
    }

    require_once $script_path;
    exit;
}

// Definir script a ser carregado
$script = null;
switch ($page) {
    case '404':
        $script = '404.php';
        break;
    case 'login':
        $script = 'login.php';
        break;
    case 'login_submit':
        $script = 'login_submit.php';
        break;
    case 'home':
        $script = 'home.php';
        break;
    case 'trocar_senha':
        $script = 'trocar_senha.php';
        break;
    case 'cadastro_visitantes':
        requererAcesso('user');
        $script = 'cadastro_visitantes.php';
        break;
    case 'listar_visitantes':
        requererAcesso('user');
        $script = 'listar_visitantes.php';
        break;
    case 'relatorios_visitantes':
        requererAcesso('supervisor');
        $script = 'relatorios_visitantes.php';
        break;
    case 'nao_autorizado':
        $script = 'nao_autorizado.php';
        break;
    case 'administracao':
    case 'cadastroUsuario':
    case 'listarUsuario':
    case 'editarUsuario':
    case 'deletarUsuario':
    case 'bloquearUsuario':
    case 'ativarUsuario':
    case 'admin_auditoria':
    case 'admin_login_locks':
    case 'desbloquear_login':
        requererAcesso('admin');
        $script = $page . '.php';
        break;
    default:
        $script = '404.php';
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/header.php';

if (file_exists(__DIR__ . '/../scripts/' . $script)) {
    require_once __DIR__ . '/../scripts/' . $script;
} else {
    require_once __DIR__ . '/../scripts/404.php';
}

// Passar variaveis para o footer
$GLOBALS['time_left'] = $time_left;
$GLOBALS['tempo_maximo'] = $tempo_maximo;

require_once __DIR__ . '/../inc/footer.php';

