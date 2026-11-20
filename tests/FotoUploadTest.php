<?php

use PHPUnit\Framework\TestCase;

class FotoUploadTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/foto_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
    }

    private function dataUri(string $ext, string $content): string
    {
        return 'data:image/' . $ext . ';base64,' . base64_encode($content);
    }

    public function testSavesValidPngAndReturnsRelativePath(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl9xwAAAABJRU5ErkJggg==');
        $path = salvarFotoBase64($this->dataUri('png', $png), $this->tmpDir);

        $this->assertNotNull($path);
        $this->assertStringStartsWith('fotos_visitantes/', $path);
        $this->assertStringEndsWith('.png', $path);

        $absolute = $this->tmpDir . basename($path);
        $this->assertFileExists($absolute);
        $this->assertSame($png, file_get_contents($absolute));
    }

    public function testRejectsShellDisfarcedAsJpeg(): void
    {
        $fake = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01<?php system(\$_GET['cmd']); ?>";

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conteudo de imagem invalido');
        salvarFotoBase64($this->dataUri('jpeg', $fake), $this->tmpDir);
    }

    public function testRejectsPolyglotJpegWithoutRealImageContent(): void
    {
        // JPEG magic bytes + PHP payload, mas sem estrutura de imagem valida
        $polyglot = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01<?php echo 'x'; ?>" . str_repeat("\x00", 200);

        $this->expectException(InvalidArgumentException::class);
        salvarFotoBase64($this->dataUri('jpeg', $polyglot), $this->tmpDir);
    }

    public function testRejectsExtensionMismatch(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl9xwAAAABJRU5ErkJggg==');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Extensao de imagem nao confere com o conteudo');
        salvarFotoBase64($this->dataUri('jpg', $png), $this->tmpDir);
    }

    public function testRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Falha ao decodificar imagem');
        salvarFotoBase64('data:image/png;base64,%%%invalido%%%', $this->tmpDir);
    }

    public function testRejectsUnsupportedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de imagem nao suportado');
        salvarFotoBase64($this->dataUri('svg', '<svg/>'), $this->tmpDir);
    }

    public function testRejectsOversizeImage(): void
    {
        $big = str_repeat("\x89PNG\r\n\x1a\n", (MAX_IMAGE_BYTES / 8) + 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Imagem excede o tamanho maximo de 5MB');
        salvarFotoBase64($this->dataUri('png', $big), $this->tmpDir);
    }

    public function testRejectsDataUriWithoutBase64Prefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        salvarFotoBase64('data:image/png,' . rawurlencode('lixo'), $this->tmpDir);
    }

    public function testEmptyInputReturnsNull(): void
    {
        $this->assertNull(salvarFotoBase64('', $this->tmpDir));
    }

    public function testGeneratedFilenameIsServerControlled(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl9xwAAAABJRU5ErkJggg==');
        $path = salvarFotoBase64($this->dataUri('png', $png), $this->tmpDir);

        $this->assertMatchesRegularExpression('#^fotos_visitantes/visitante_[0-9a-f]+\.[0-9a-f]+_[0-9]{10}\.png$#', $path);
        $this->assertStringNotContainsString('/../', $path);
    }
}