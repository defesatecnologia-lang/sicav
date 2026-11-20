<?php

declare(strict_types=1);

putenv('VISITORPASS_DB_PASS=ci-smoke-test');

const BASE_URL = 'http://127.0.0.1:8877/index.php';

$tests = [];

function httpGet(string $url): string
{
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException("Falha ao acessar $url: " . error_get_last()['message'] ?? 'erro desconhecido');
    }
    return $body;
}

function assertContains(string $haystack, string $needle, string $label): void
{
    global $tests;
    $tests[] = [str_contains($haystack, $needle), $label];
}

$login = httpGet(BASE_URL . '?page=login');

assertContains($login, '<!DOCTYPE html>', 'login renderiza DOCTYPE html');
assertContains($login, '<html', 'login tem tag <html>');
assertContains($login, '<head>', 'login tem <head>');
assertContains($login, '<form', 'login tem <form>');
assertContains($login, 'name="text_usuario"', 'login tem campo usuario');
assertContains($login, 'name="text_senha"', 'login tem campo senha');
assertContains($login, 'name="csrf_token"', 'login tem csrf_token');
assertContains($login, 'name="csrf_token" value="', 'csrf_token tem valor');
assertContains($login, '<script', 'login inclui <script>');

$body404 = @file_get_contents(__DIR__ . '/../../public/404.html');
$tests[] = [$body404 !== false && stripos($body404, '<!doctype html>') !== false, '404.html eh HTML valido'];

$failures = array_filter($tests, fn($t) => !$t[0]);
$count = count($tests);

foreach ($failures as $f) {
    fwrite(STDERR, "FALHOU: {$f[1]}\n");
}

echo "HTML tests: $count checks, " . (count($tests) - count($failures)) . " pass, " . count($failures) . " fail\n";

exit(count($failures) > 0 ? 1 : 0);