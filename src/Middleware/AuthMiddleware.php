<?php

namespace App\Middleware;

use App\HttpException;

class AuthMiddleware implements MiddlewareInterface
{
    private string $requiredLevel;

    public function __construct(string $requiredLevel = 'user')
    {
        $this->requiredLevel = $requiredLevel;
    }

    public function handle(callable $next): mixed
    {
        $isApi = isset($_GET['page']) && str_starts_with($_GET['page'], 'api/');

        if (!isset($_SESSION['usuario'])) {
            throw $isApi
                ? new HttpException(401, json_encode(['success' => false, 'error' => 'Nao autorizado']))
                : HttpException::redirect('index.php?page=login');
        }

        $niveis = ['user' => 1, 'supervisor' => 2, 'admin' => 3];
        $nivelUsuario = $niveis[$_SESSION['usuario']->nivel_acesso ?? ''] ?? 0;
        $nivelRequerido = $niveis[$this->requiredLevel] ?? 1;

        if ($nivelUsuario < $nivelRequerido) {
            throw $isApi
                ? new HttpException(403, json_encode(['success' => false, 'error' => 'Acesso negado']))
                : HttpException::redirect('index.php?page=nao_autorizado');
        }

        return $next();
    }
}
