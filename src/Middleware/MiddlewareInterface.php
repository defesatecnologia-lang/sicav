<?php

namespace App\Middleware;

interface MiddlewareInterface
{
    public function handle(callable $next): mixed;
}
