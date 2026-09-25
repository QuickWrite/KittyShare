<?php

use KittyShare\Http\Response;
use KittyShare\Http\TemplateResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TemplateResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        http_response_code(200);

        parent::tearDown();
    }

    #[Test]
    public function implementsResponse(): void
    {
        $this->assertInstanceOf(Response::class, new TemplateResponse('error/404'));
    }

    #[Test]
    public function storesConstructorArguments(): void
    {
        $response = new TemplateResponse(
            'error/404',
            ['path' => '/missing', 'baseUrl' => ''],
            404,
            ['X-Custom' => 'value'],
        );

        $this->assertSame('error/404', $this->readProperty($response, 'template'));
        $this->assertSame(['path' => '/missing', 'baseUrl' => ''], $this->readProperty($response, 'parameters'));
        $this->assertSame(404, $this->readProperty($response, 'statusCode'));
        $this->assertSame(['X-Custom' => 'value'], $this->readProperty($response, 'headers'));
    }

    #[Test]
    public function defaultsTo200WithEmptyParametersAndHeaders(): void
    {
        $response = new TemplateResponse('error/404');

        $this->assertSame(200, $this->readProperty($response, 'statusCode'));
        $this->assertSame([], $this->readProperty($response, 'parameters'));
        $this->assertSame([], $this->readProperty($response, 'headers'));
    }

    #[Test]
    public function rendersTemplateWithStatusCode(): void
    {
        $response = new TemplateResponse(
            'error/404',
            ['path' => '/missing', 'baseUrl' => ''],
            404,
        );

        ob_start();
        try {
            $response->send();
        } finally {
            $output = (string) ob_get_clean();
        }

        $this->assertSame(404, http_response_code());
        $this->assertStringContainsString('404 Page not found', $output);
        $this->assertStringContainsString('/missing', $output);
    }

    #[Test]
    public function escapesTemplateParameters(): void
    {
        $response = new TemplateResponse(
            'error/404',
            ['path' => '/a<b>&"c"', 'baseUrl' => ''],
            404,
        );

        ob_start();
        try {
            $response->send();
        } finally {
            $output = (string) ob_get_clean();
        }

        $this->assertStringContainsString('/a&lt;b&gt;&amp;&quot;c&quot;', $output);
        $this->assertStringNotContainsString('/a<b>', $output);
    }

    private function readProperty(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
