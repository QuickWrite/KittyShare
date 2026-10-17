<?php

namespace KittyShare\Tests\Controller;

use KittyShare\Controller\NotFoundController;
use KittyShare\Http\Request;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Manager\TranslationManager;
use KittyShare\Model\Dependencies;
use KittyShare\Repository\SessionRepository;
use KittyShare\Repository\SetupRepository;
use KittyShare\Repository\ShareRepository;
use KittyShare\Repository\UserRepository;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class NotFoundControllerTest extends TestCase
{
    private NotFoundController $controller;

    protected function setUp(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $sessionRepository = $this->createStub(SessionRepository::class);

        $dependencies = new Dependencies(
            userRepository: $userRepository,
            setupRepository: $this->createStub(SetupRepository::class),
            sessionRepository: $sessionRepository,
            shareRepository: $this->createStub(ShareRepository::class),
            authenticationManager: new AuthenticationManager($userRepository, $sessionRepository),
        );

        $this->controller = new NotFoundController($dependencies);
    }

    public function testHandleReturnsTemplateResponse(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getUri')
            ->willReturn('/missing-page');

        $response = $this->controller->handle($request);

        self::assertInstanceOf(TemplateResponse::class, $response);
    }

    public function testHandleReturns404StatusCode(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getUri')->willReturn('/missing-page');

        $response = $this->controller->handle($request);

        self::assertSame(404, $this->getResponseProperty($response, 'statusCode'));
    }

    public function testHandleUses404Template(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getUri')->willReturn('/missing-page');

        $response = $this->controller->handle($request);

        self::assertSame('error/404', $this->getResponseProperty($response, 'template'));
    }

    public function testHandlePassesRequestUriToTemplate(): void
    {
        $uri = '/some/nonexistent/page?foo=bar';

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('getUri')
            ->willReturn($uri);

        $response = $this->controller->handle($request);

        $parameters = $this->getResponseProperty($response, 'parameters');

        self::assertSame($uri, $parameters['path']);
    }

    public function testHandlePassesConfiguredBaseUrlToTemplate(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getUri')->willReturn('/missing-page');

        $response = $this->controller->handle($request);

        $parameters = $this->getResponseProperty($response, 'parameters');

        self::assertSame(ConfigManager::get()->baseUrl, $parameters['baseUrl']);
    }

    public function testHandlePassesTranslationManagerToTemplate(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('getUri')->willReturn('/missing-page');

        $response = $this->controller->handle($request);

        $parameters = $this->getResponseProperty($response, 'parameters');

        self::assertInstanceOf(
            TranslationManager::class,
            $parameters['translationManager'],
        );
    }

    /**
     * Reads a private property from TemplateResponse.
     */
    private function getResponseProperty(
        TemplateResponse $response,
        string $property,
    ): mixed {
        return (new ReflectionProperty(TemplateResponse::class, $property))
            ->getValue($response);
    }
}
