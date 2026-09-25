<?php

use KittyShare\Http\FileResponse;
use KittyShare\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ProcessRunner.php';

final class FileResponseTest extends TestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        if ($this->file !== '' && is_file($this->file)) {
            unlink($this->file);
        }

        $this->file = '';

        parent::tearDown();
    }

    #[Test]
    public function implementsResponse(): void
    {
        $this->assertInstanceOf(Response::class, new FileResponse('/tmp/example.txt', 'text/plain'));
    }

    #[Test]
    public function storesConstructorArguments(): void
    {
        $response = new FileResponse('/tmp/example.txt', 'text/plain', 201, 4096);

        $this->assertSame('/tmp/example.txt', $this->readProperty($response, 'file'));
        $this->assertSame('text/plain', $this->readProperty($response, 'contentType'));
        $this->assertSame(201, $this->readProperty($response, 'statusCode'));
        $this->assertSame(4096, $this->readProperty($response, 'chunkSize'));
    }

    #[Test]
    public function defaultsToStatus200And8192ByteChunks(): void
    {
        $response = new FileResponse('/tmp/example.txt', 'text/plain');

        $this->assertSame(200, $this->readProperty($response, 'statusCode'));
        $this->assertSame(8192, $this->readProperty($response, 'chunkSize'));
    }

    #[Test]
    public function streamsFileContentsInChunks(): void
    {
        // Content is larger than the chunk size so the read loop runs
        // multiple times, and its length is not a multiple of the chunk
        // size so the final partial chunk is covered as well.
        $content = str_repeat("0123456789abcdef\n", 500);
        $this->file = $this->writeTempFile($content);

        $result = ProcessRunner::run($this->sendSnippet($this->file, 'text/plain', 200, 100));

        $this->assertSame(0, $result['exit'], 'stderr: ' . $result['stderr']);
        $this->assertSame($content, $result['stdout']);
        $this->assertStringContainsString('CODE:200', $result['stderr']);
    }

    #[Test]
    public function streamsEmptyFile(): void
    {
        $this->file = $this->writeTempFile('');

        $result = ProcessRunner::run($this->sendSnippet($this->file, 'text/plain', 200, 100));

        $this->assertSame(0, $result['exit'], 'stderr: ' . $result['stderr']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('CODE:200', $result['stderr']);
    }

    #[Test]
    public function missingFileReturns404WithoutOutput(): void
    {
        $missing = sys_get_temp_dir() . '/kittyshare-missing-' . uniqid() . '.txt';

        $result = ProcessRunner::run($this->sendSnippet($missing, 'text/plain', 200, 100));

        $this->assertSame(0, $result['exit'], 'stderr: ' . $result['stderr']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('CODE:404', $result['stderr']);
    }

    private function writeTempFile(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'kittyshare-file-');

        if ($file === false) {
            $this->fail('Could not create temporary file.');
        }

        file_put_contents($file, $content);

        return $file;
    }

    private function sendSnippet(string $file, string $contentType, int $statusCode, int $chunkSize): string
    {
        return sprintf(
            'require %s; (new \KittyShare\Http\FileResponse(%s, %s, %d, %d))->send(); fwrite(STDERR, "CODE:".var_export(http_response_code(), true));',
            ProcessRunner::autoload(),
            var_export($file, true),
            var_export($contentType, true),
            $statusCode,
            $chunkSize,
        );
    }

    private function readProperty(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
