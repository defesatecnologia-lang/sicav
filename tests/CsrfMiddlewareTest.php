<?php

use App\Middleware\CsrfMiddleware;
use App\HttpException;
use PHPUnit\Framework\TestCase;

class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION['csrf_token'] = 'valid-token-123';
    }

    protected function tearDown(): void
    {
        unset($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'], $_POST['csrf_token']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testPassesGetRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testPassesPostWithValidHeaderToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid-token-123';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testPassesPostWithValidPostToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'valid-token-123';
        $middleware = new CsrfMiddleware();

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testDeniesPostWithInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong-token';
        $middleware = new CsrfMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(419);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testDeniesPostWithMissingToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $middleware = new CsrfMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(419);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testDeniesPostWithEmptySessionToken(): void
    {
        unset($_SESSION['csrf_token']);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'anything';
        $middleware = new CsrfMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(419);

        $middleware->handle(fn() => 'should-not-reach');
    }
}
