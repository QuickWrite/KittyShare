<?php

use KittyShare\Controller\BaseController;
use KittyShare\Http\Method;
use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Request;
use KittyShare\Http\Response;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BaseControllerTestStub extends BaseController
{
    public function handle(Request $request): Response
    {
        return $this->redirect('/target');
    }
}

final class BaseControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
    }

    protected function tearDown(): void
    {
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        parent::tearDown();
    }

    private function dependencies(): Dependencies
    {
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $sessions = $this->createStub(KittyShare\Repository\SessionRepository::class);
        return new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $this->createStub(KittyShare\Repository\ShareRepository::class),
            authenticationManager: new AuthenticationManager($users, $sessions),
        );
    }

    #[Test]
    public function redirectPrefixesBaseUrl(): void
    {
        $response = (new BaseControllerTestStub($this->dependencies()))->handle(new Request(Method::Get, '/', []));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/target', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function redirectIncludesBasePathWhenConfigured(): void
    {
        putenv('KITTYSHARE_BASE_URL=/app');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        $response = (new BaseControllerTestStub($this->dependencies()))->handle(new Request(Method::Get, '/', []));

        $this->assertSame('/app/target', (new ReflectionProperty($response, 'url'))->getValue($response));
    }
}
