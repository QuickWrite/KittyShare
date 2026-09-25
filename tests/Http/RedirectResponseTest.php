<?php

use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RedirectResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        http_response_code(200);

        parent::tearDown();
    }

    #[Test]
    public function implementsResponse(): void
    {
        $this->assertInstanceOf(Response::class, new RedirectResponse('/login'));
    }

    #[Test]
    public function defaultsTo302(): void
    {
        $response = new RedirectResponse('/login');

        $this->assertSame('/login', $this->readProperty($response, 'url'));
        $this->assertSame(302, $this->readProperty($response, 'statusCode'));
    }

    #[Test]
    public function acceptsCustomStatusCode(): void
    {
        $response = new RedirectResponse('/login', 301);

        $this->assertSame(301, $this->readProperty($response, 'statusCode'));
    }

    #[Test]
    public function sendSetsResponseCode(): void
    {
        (new RedirectResponse('/login', 303))->send();

        $this->assertSame(303, http_response_code());
    }

    private function readProperty(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
