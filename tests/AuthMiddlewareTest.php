<?php

use App\Middleware\AuthMiddleware;
use App\HttpException;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    private ?object $originalSession;
    private string|array|null $originalGet;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION['usuario'] ?? null;
        $this->originalGet = $_GET;
        $_GET['page'] = 'api/test';
    }

    protected function tearDown(): void
    {
        $_SESSION['usuario'] = $this->originalSession;
        $_GET = $this->originalGet;
    }

    public function testAllowsAuthenticatedUser(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'user'];
        $middleware = new AuthMiddleware('user');

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testDeniesUnauthenticatedApiRequest(): void
    {
        unset($_SESSION['usuario']);
        $_GET['page'] = 'api/session_check';
        $middleware = new AuthMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testRedirectsUnauthenticatedPageRequest(): void
    {
        unset($_SESSION['usuario']);
        $_GET['page'] = 'home';
        $middleware = new AuthMiddleware();

        try {
            $middleware->handle(fn() => 'should-not-reach');
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
            $this->assertTrue($e->isRedirect());
            $this->assertSame('index.php?page=login', $e->getRedirectUrl());
        }
    }

    public function testDeniesInsufficientLevelApiRequest(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'user'];
        $_GET['page'] = 'api/export';
        $middleware = new AuthMiddleware('admin');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testAllowsSufficientLevel(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'admin'];
        $middleware = new AuthMiddleware('supervisor');

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testDefaultLevelIsUser(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'user'];
        $middleware = new AuthMiddleware();

        $result = $middleware->handle(fn() => 'passed');
        $this->assertSame('passed', $result);
    }

    public function testUserLevelCannotAccessSupervisor(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'user'];
        $_GET['page'] = 'api/admin';
        $middleware = new AuthMiddleware('supervisor');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testUnknownLevelIsDeniedApi(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'nonexistent'];
        $_GET['page'] = 'api/test';
        $middleware = new AuthMiddleware('user');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        $middleware->handle(fn() => 'should-not-reach');
    }

    public function testRedirectsInsufficientLevelPageRequest(): void
    {
        $_SESSION['usuario'] = (object) ['nivel_acesso' => 'user'];
        $_GET['page'] = 'relatorios_visitantes';
        $middleware = new AuthMiddleware('supervisor');

        try {
            $middleware->handle(fn() => 'should-not-reach');
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
            $this->assertTrue($e->isRedirect());
            $this->assertSame('index.php?page=nao_autorizado', $e->getRedirectUrl());
        }
    }

    public function testDeniesUnauthenticatedPageRequestDoesNotThrowHttpException(): void
    {
        unset($_SESSION['usuario']);
        $_GET['page'] = 'home';
        $middleware = new AuthMiddleware();

        try {
            $middleware->handle(fn() => 'should-not-reach');
            $this->fail('Expected HttpException');
        } catch (HttpException $e) {
            $this->assertTrue($e->isRedirect());
            $this->assertSame('', $e->getResponseBody());
        }
    }
}
