<?php

namespace App\Middleware;

use App\HttpException;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
            if (!validateCsrfToken($token)) {
                throw new HttpException(419, json_encode(['success' => false, 'error' => 'CSRF token invalido']));
            }
        }

        return $next();
    }
}
