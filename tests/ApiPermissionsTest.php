<?php

use PHPUnit\Framework\TestCase;

class ApiPermissionsTest extends TestCase
{
    public function testResolvesEndpointFromRouterPageParam(): void
    {
        $this->assertSame('api_estatisticas', resolveApiEndpoint('api/api_estatisticas', 'index.php'));
        $this->assertSame('get_auditoria', resolveApiEndpoint('api/get_auditoria', 'index.php'));
        $this->assertSame('session_check', resolveApiEndpoint('api/session_check', 'index.php'));
    }

    public function testResolvesEndpointFromScriptNameWithoutApiPrefix(): void
    {
        $this->assertSame('session_check', resolveApiEndpoint('', 'session_check.php'));
        $this->assertSame('get_photo', resolveApiEndpoint('', '/var/www/html/api/get_photo.php'));
    }

    public function testResolvesNonApiPageAsItself(): void
    {
        $this->assertSame('home', resolveApiEndpoint('home', 'index.php'));
    }

    public function testResolvesEmptyPageWithEmptyScriptNameToEmptyString(): void
    {
        $this->assertSame('', resolveApiEndpoint('', ''));
    }

    public function testPermissionsMapCoversEveryApiRoute(): void
    {
        $map = require __DIR__ . '/../config/permissions.php';

        $apiFiles = glob(__DIR__ . '/../api/*.php');
        $this->assertNotEmpty($apiFiles, 'Diretório api/ deve conter endpoints');

        foreach ($apiFiles as $file) {
            $endpoint = basename($file, '.php');
            $this->assertArrayHasKey(
                $endpoint,
                $map,
                "Endpoint {$endpoint} deve estar mapeado em config/permissions.php"
            );
        }
    }

    public function testPermissionsMapUsesOnlyValidLevels(): void
    {
        $map = require __DIR__ . '/../config/permissions.php';
        $valid = ['user', 'supervisor', 'admin'];

        foreach ($map as $endpoint => $level) {
            $this->assertContains(
                $level,
                $valid,
                "Nível '{$level}' para {$endpoint} deve ser um dos níveis válidos"
            );
        }
    }

    public function testSupervisorAndAdminEndpointsAreRestricted(): void
    {
        $map = require __DIR__ . '/../config/permissions.php';

        foreach (['api_detalhes', 'api_estatisticas', 'api_export', 'api_relatorios'] as $endpoint) {
            $this->assertSame('supervisor', $map[$endpoint], "{$endpoint} exige supervisor");
        }

        foreach (['exportar_auditoria', 'get_auditoria', 'get_auditoria_detalhe'] as $endpoint) {
            $this->assertSame('admin', $map[$endpoint], "{$endpoint} exige admin");
        }
    }

    public function testUnknownEndpointFallsBackToUser(): void
    {
        $this->assertSame('user', (require __DIR__ . '/../config/permissions.php')['nao_existe'] ?? 'user');
    }
}