<?php

use KittyShare\Controller\BaseController;
use KittyShare\Http\Method;
use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Request;
use KittyShare\Http\Response;
use KittyShare\Http\Router;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Repository\SessionRepository;
use KittyShare\Repository\SetupRepository;
use KittyShare\Repository\ShareRepository;
use KittyShare\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouterTestStubController extends BaseController
{
    public static ?Request $lastRequest = null;

    public function handle(Request $request): Response
    {
        self::$lastRequest = $request;

        return new RedirectResponse('/stub');
    }
}

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();

        RouterTestStubController::$lastRequest = null;
        $this->resetConfig();

        $userRepository = $this->createStub(UserRepository::class);
        $sessionRepository = $this->createStub(SessionRepository::class);

        $this->router = new Router(
            new Dependencies(
                userRepository: $userRepository,
                setupRepository: $this->createStub(SetupRepository::class),
                sessionRepository: $sessionRepository,
                shareRepository: $this->createStub(ShareRepository::class),
                authenticationManager: new AuthenticationManager($userRepository, $sessionRepository),
            ),
        );
    }

    protected function tearDown(): void
    {
        RouterTestStubController::$lastRequest = null;
        $this->resetConfig();

        parent::tearDown();
    }

    #[Test]
    public function dispatchesExactRoute(): void
    {
        $this->router->get('/login', RouterTestStubController::class);

        $response = $this->router->dispatch(Method::Get, '/login');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotNull(RouterTestStubController::$lastRequest);
        $this->assertSame([], RouterTestStubController::$lastRequest->getUrlParams());
    }

    #[Test]
    public function extractsSingleParameter(): void
    {
        $this->router->get('/share/{id}', RouterTestStubController::class);

        $this->router->dispatch(Method::Get, '/share/abc123');

        $this->assertSame(['id' => 'abc123'], RouterTestStubController::$lastRequest?->getUrlParams());
    }

    #[Test]
    public function extractsCatchAllPathAcrossSegments(): void
    {
        $this->router->get('/share/{id}/{...path}', RouterTestStubController::class);

        $this->router->dispatch(Method::Get, '/share/abc/folder/sub/file.txt');

        $this->assertSame(
            ['id' => 'abc', 'path' => 'folder/sub/file.txt'],
            RouterTestStubController::$lastRequest?->getUrlParams(),
        );
    }

    #[Test]
    public function normalizesTrailingSlashes(): void
    {
        $this->router->get('/admin', RouterTestStubController::class);

        $response = $this->router->dispatch(Method::Get, '/admin/');

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function preservesRootPath(): void
    {
        $this->router->get('/', RouterTestStubController::class);

        $response = $this->router->dispatch(Method::Get, '/');

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function firstMatchingRouteWins(): void
    {
        $this->router->get('/users/{id}', RouterTestStubController::class);
        $this->router->get('/users/new', RouterTestStubController::class);

        $this->router->dispatch(Method::Get, '/users/new');

        $this->assertSame(['id' => 'new'], RouterTestStubController::$lastRequest?->getUrlParams());
    }

    #[Test]
    public function distinguishesHttpMethods(): void
    {
        $this->router->get('/login', RouterTestStubController::class);
        $this->router->post('/submit', RouterTestStubController::class);
        $this->router->put('/item', RouterTestStubController::class);
        $this->router->delete('/item', RouterTestStubController::class);

        $this->assertInstanceOf(RedirectResponse::class, $this->router->dispatch(Method::Post, '/submit'));
        $this->assertInstanceOf(RedirectResponse::class, $this->router->dispatch(Method::Put, '/item'));
        $this->assertInstanceOf(RedirectResponse::class, $this->router->dispatch(Method::Delete, '/item'));

        // Registered for GET only, so POST falls through to the 404 page.
        $this->assertInstanceOf(TemplateResponse::class, $this->router->dispatch(Method::Post, '/login'));
    }

    #[Test]
    public function unknownPathReturns404Template(): void
    {
        $response = $this->router->dispatch(Method::Get, '/does-not-exist');

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('error/404', $this->readProperty($response, 'template'));
        $this->assertSame(404, $this->readProperty($response, 'statusCode'));
        $this->assertSame('/does-not-exist', $this->readProperty($response, 'parameters')['path']);
    }

    #[Test]
    public function decodesUrlEncodedParameters(): void
    {
        $this->router->get('/share/{id}/{...path}', RouterTestStubController::class);

        $this->router->dispatch(Method::Get, '/share/abc/my%20file.txt');

        $this->assertSame('my file.txt', RouterTestStubController::$lastRequest?->urlParam('path'));
    }

    #[Test]
    public function preservesLiteralPlusInParameters(): void
    {
        $this->router->get('/share/{id}/{...path}', RouterTestStubController::class);

        // rawurldecode (not urldecode) keeps a literal "+" intact.
        $this->router->dispatch(Method::Get, '/share/abc/a+b');

        $this->assertSame('a+b', RouterTestStubController::$lastRequest?->urlParam('path'));

        $this->router->dispatch(Method::Get, '/share/abc/a%2Bb');

        $this->assertSame('a+b', RouterTestStubController::$lastRequest?->urlParam('path'));
    }

    private function resetConfig(): void
    {
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
    }

    private function readProperty(object $object, string $name): mixed
    {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
