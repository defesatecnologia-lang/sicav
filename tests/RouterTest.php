<?php

use App\Router;
use App\Middleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $_SERVER['REQUEST_URI'] = '/sistema-visitantes-v1.0/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        unset($_GET['page']);
    }

    public function testGetRouteMatchesCorrectMethod(): void
    {
        $this->router->get('test-page', fn() => 'get-called');

        $_GET['page'] = 'test-page';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('get-called', $this->router->dispatch());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = $this->router->dispatch();
        $this->assertNull($result);
    }

    public function testPostRouteMatchesCorrectMethod(): void
    {
        $this->router->post('test-page', fn() => 'post-called');

        $_GET['page'] = 'test-page';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame('post-called', $this->router->dispatch());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $result = $this->router->dispatch();
        $this->assertNull($result);
    }

    public function testAnyRouteMatchesAllMethods(): void
    {
        $this->router->any('any-page', fn() => 'any-called');

        $_GET['page'] = 'any-page';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame('any-called', $this->router->dispatch());

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame('any-called', $this->router->dispatch());

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $this->assertSame('any-called', $this->router->dispatch());
    }

    public function testWildcardRouteMatchesAnyPage(): void
    {
        $this->router->any('*', fn() => 'wildcard');

        $_GET['page'] = 'anything-here';
        $this->assertSame('wildcard', $this->router->dispatch());
    }

    public function testRouteNotFoundReturnsNull(): void
    {
        $_GET['page'] = 'non-existent-page';

        ob_start();
        $result = $this->router->dispatch();
        ob_end_clean();

        $this->assertNull($result);
    }

    public function testRouteMiddlewareExecutesBeforeHandler(): void
    {
        $log = new stdClass();
        $log->entries = [];

        $middleware = new class($log) implements MiddlewareInterface {
            public function __construct(private object $log) {}
            public function handle(callable $next): mixed
            {
                $this->log->entries[] = 'middleware-start';
                $result = $next();
                $this->log->entries[] = 'middleware-end';
                return $result;
            }
        };

        $this->router->get('test-page', function () use ($log) {
            $log->entries[] = 'handler';
            return 'done';
        }, [$middleware]);

        $_GET['page'] = 'test-page';
        $this->assertSame('done', $this->router->dispatch());
        $this->assertSame(['middleware-start', 'handler', 'middleware-end'], $log->entries);
    }

    public function testGlobalMiddlewareAddsBeforeRouteMiddleware(): void
    {
        $log = new stdClass();
        $log->entries = [];

        $global = new class($log) implements MiddlewareInterface {
            public function __construct(private object $log) {}
            public function handle(callable $next): mixed
            {
                $this->log->entries[] = 'global';
                return $next();
            }
        };

        $route = new class($log) implements MiddlewareInterface {
            public function __construct(private object $log) {}
            public function handle(callable $next): mixed
            {
                $this->log->entries[] = 'route';
                return $next();
            }
        };

        $this->router->addGlobalMiddleware($global);
        $this->router->get('test-page', function () use ($log) {
            $log->entries[] = 'handler';
            return 'done';
        }, [$route]);

        $_GET['page'] = 'test-page';
        $this->router->dispatch();

        $this->assertSame(['global', 'route', 'handler'], $log->entries);
    }

    public function testGlobalMiddlewareDoesNotRunOnUnmatchedRoute(): void
    {
        $state = new stdClass();
        $state->ran = false;

        $global = new class($state) implements MiddlewareInterface {
            public function __construct(private object $state) {}
            public function handle(callable $next): mixed
            {
                $this->state->ran = true;
                return $next();
            }
            public function hasRan(): bool { return $this->state->ran; }
        };

        $this->router->addGlobalMiddleware($global);
        $this->router->get('exists', fn() => 'ok');

        $_GET['page'] = 'does-not-exist';

        ob_start();
        $this->router->dispatch();
        ob_end_clean();

        $this->assertFalse($state->ran);
    }

    public function testDefaultPageIsHome(): void
    {
        $this->router->get('home', fn() => 'home-called');
        unset($_GET['page']);

        $this->assertSame('home-called', $this->router->dispatch());
    }

    public function testUriTrailingSlashIsNormalized(): void
    {
        $this->router->get('test-page', fn() => 'ok');
        $_GET['page'] = 'test-page';

        $this->assertSame('ok', $this->router->dispatch());
    }
}
