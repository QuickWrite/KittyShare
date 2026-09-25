<?php

use KittyShare\Http\FileResponse;
use KittyShare\Http\FileResponseFactory;
use KittyShare\Http\XSendfileResponse;
use KittyShare\Manager\ConfigManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileResponseFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetConfig();
    }

    protected function tearDown(): void
    {
        putenv('KITTYSHARE_FILE_SERVER');
        $this->resetConfig();

        parent::tearDown();
    }

    #[Test]
    public function createsByDefaultFileResponse(): void
    {
        putenv('KITTYSHARE_FILE_SERVER');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain');

        $this->assertInstanceOf(FileResponse::class, $response);
    }

    #[Test]
    public function createsFileResponseWhenPhpIsConfigured(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=php');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain');

        $this->assertInstanceOf(FileResponse::class, $response);
    }

    #[Test]
    public function createsXSendfileResponseWhenConfigured(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=x-sendfile');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain');

        $this->assertInstanceOf(XSendfileResponse::class, $response);
    }

    #[Test]
    public function createsXSendfileResponseForApacheAlias(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=apache');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain');

        $this->assertInstanceOf(XSendfileResponse::class, $response);
    }

    #[Test]
    public function fallsBackToFileResponseForUnknownBackend(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=unknown-backend');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain');

        $this->assertInstanceOf(FileResponse::class, $response);
    }

    #[Test]
    public function forwardsArgumentsToFileResponse(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=php');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain', 201, 4096);

        $this->assertInstanceOf(FileResponse::class, $response);
        $this->assertSame('/tmp/example.txt', $this->readProperty($response, 'file'));
        $this->assertSame('text/plain', $this->readProperty($response, 'contentType'));
        $this->assertSame(201, $this->readProperty($response, 'statusCode'));
        $this->assertSame(4096, $this->readProperty($response, 'chunkSize'));
    }

    #[Test]
    public function forwardsArgumentsToXSendfileResponse(): void
    {
        putenv('KITTYSHARE_FILE_SERVER=x-sendfile');
        $this->resetConfig();

        $response = FileResponseFactory::forFile('/tmp/example.txt', 'text/plain', 201);

        $this->assertInstanceOf(XSendfileResponse::class, $response);
        $this->assertSame('/tmp/example.txt', $this->readProperty($response, 'file'));
        $this->assertSame('text/plain', $this->readProperty($response, 'contentType'));
        $this->assertSame(201, $this->readProperty($response, 'statusCode'));
    }

    private function resetConfig(): void
    {
        $property = new ReflectionProperty(ConfigManager::class, 'config');
        $property->setValue(null, null);
    }

    private function readProperty(object $object, string $name): mixed
    {
        $property = new ReflectionProperty($object, $name);

        return $property->getValue($object);
    }
}
