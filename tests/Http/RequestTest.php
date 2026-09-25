<?php

use KittyShare\Http\Method;
use KittyShare\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $backupGet = [];

    /** @var array<string, mixed> */
    private array $backupPost = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupGet = $_GET;
        $this->backupPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_GET = $this->backupGet;
        $_POST = $this->backupPost;

        parent::tearDown();
    }

    #[Test]
    public function exposesMethodUriAndUrlParams(): void
    {
        $request = new Request(Method::Get, '/share/abc', ['id' => 'abc']);

        $this->assertSame(Method::Get, $request->getMethod());
        $this->assertSame('/share/abc', $request->getUri());
        $this->assertSame(['id' => 'abc'], $request->getUrlParams());
    }

    #[Test]
    public function readsUrlParamWithDefault(): void
    {
        $request = new Request(Method::Get, '/share/abc', ['id' => 'abc']);

        $this->assertSame('abc', $request->urlParam('id'));
        $this->assertNull($request->urlParam('missing'));
        $this->assertSame('fallback', $request->urlParam('missing', 'fallback'));
    }

    #[Test]
    public function readsQueryString(): void
    {
        $_GET = ['name' => 'ada'];

        $request = new Request(Method::Get, '/', []);

        $this->assertSame('ada', $request->get('name'));
    }

    #[Test]
    public function fallsBackToDefaultForNonStringQueryValue(): void
    {
        $_GET = ['filter' => ['not', 'a', 'string']];

        $request = new Request(Method::Get, '/', []);

        $this->assertNull($request->get('filter'));
        $this->assertSame('default', $request->get('filter', 'default'));
    }

    #[Test]
    public function readsPostBody(): void
    {
        $_POST = ['username' => 'ada'];

        $request = new Request(Method::Post, '/login', []);

        $this->assertSame('ada', $request->post('username'));
    }

    #[Test]
    public function fallsBackToDefaultForNonStringPostValue(): void
    {
        $_POST = ['username' => ['not', 'a', 'string']];

        $request = new Request(Method::Post, '/login', []);

        $this->assertNull($request->post('username'));
        $this->assertSame('default', $request->post('username', 'default'));
    }
}
