<?php

use KittyShare\Http\Response;
use KittyShare\Http\XSendfileResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/ProcessRunner.php';

final class XSendfileResponseTest extends TestCase
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
        $this->assertInstanceOf(Response::class, new XSendfileResponse('/tmp/example.txt', 'text/plain'));
    }

    #[Test]
    public function storesConstructorArguments(): void
    {
        $response = new XSendfileResponse('/tmp/example.txt', 'text/plain', 201);

        $this->assertSame('/tmp/example.txt', $this->readProperty($response, 'file'));
        $this->assertSame('text/plain', $this->readProperty($response, 'contentType'));
        $this->assertSame(201, $this->readProperty($response, 'statusCode'));
    }

    #[Test]
    public function defaultsToStatus200(): void
    {
        $response = new XSendfileResponse('/tmp/example.txt', 'text/plain');

        $this->assertSame(200, $this->readProperty($response, 'statusCode'));
    }

    #[Test]
    public function existingFileReturnsStatusCodeWithoutBody(): void
    {
        // Apache (not PHP) sends the body via the X-Sendfile header, so a
        // successful response has no body of its own. Headers cannot be
        // observed in a CLI subprocess, but the status code proves the
        // file-exists branch was taken.
        $this->file = tempnam(sys_get_temp_dir(), 'kittyshare-xsend-');

        if ($this->file === false) {
            $this->fail('Could not create temporary file.');
        }

        file_put_contents($this->file, 'hello');

        $result = ProcessRunner::run($this->sendSnippet($this->file, 'text/plain', 200));

        $this->assertSame(0, $result['exit'], 'stderr: ' . $result['stderr']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('CODE:200', $result['stderr']);
    }

    #[Test]
    public function missingFileReturns404WithoutOutput(): void
    {
        $missing = sys_get_temp_dir() . '/kittyshare-missing-' . uniqid() . '.txt';

        $result = ProcessRunner::run($this->sendSnippet($missing, 'text/plain', 200));

        $this->assertSame(0, $result['exit'], 'stderr: ' . $result['stderr']);
        $this->assertSame('', $result['stdout']);
        $this->assertStringContainsString('CODE:404', $result['stderr']);
    }

    private function sendSnippet(string $file, string $contentType, int $statusCode): string
    {
        return sprintf(
            'require %s; (new \KittyShare\Http\XSendfileResponse(%s, %s, %d))->send(); fwrite(STDERR, "CODE:".var_export(http_response_code(), true));',
            ProcessRunner::autoload(),
            var_export($file, true),
            var_export($contentType, true),
            $statusCode,
        );
    }

    private function readProperty(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
