<?php

use KittyShare\Controller\LogoutController;
use KittyShare\Http\Method;
use KittyShare\Http\Request;
use KittyShare\Http\Session as HttpSession;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Model\Session;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SessionRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakeLogoutSessionRepository implements SessionRepository
{
    public ?Session $deleted = null;

    public function __construct(private ?Session $current = null)
    {
    }

    public function create(UserIdentity $user): Session
    {
        throw new RuntimeException('not used');
    }

    public function find(string $id): ?Session
    {
        return $this->current;
    }

    public function delete(Session $session): void
    {
        $this->deleted = $session;
    }
}

final class LogoutControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('auth.session_id');
    }

    protected function tearDown(): void
    {
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('auth.session_id');
        parent::tearDown();
    }

    #[Test]
    public function logoutCallsManagerAndRedirectsToLogin(): void
    {
        $session = new Session('sid', new UserIdentity(1, 'admin'), new DateTimeImmutable('+1 hour'));
        $sessions = new FakeLogoutSessionRepository($session);
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $deps = new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $this->createStub(KittyShare\Repository\ShareRepository::class),
            authenticationManager: new AuthenticationManager($users, $sessions),
        );

        HttpSession::set('auth.session_id', 'sid');

        $response = (new LogoutController($deps))->handle(new Request(Method::Post, '/logout', []));

        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
        $this->assertSame($session, $sessions->deleted);
        $this->assertNull(HttpSession::get('auth.session_id'));
    }

    #[Test]
    public function logoutRegeneratesSessionId(): void
    {
        $session = new Session('sid', new UserIdentity(1, 'admin'), new DateTimeImmutable('+1 hour'));
        $sessions = new FakeLogoutSessionRepository($session);
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $deps = new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $this->createStub(KittyShare\Repository\ShareRepository::class),
            authenticationManager: new AuthenticationManager($users, $sessions),
        );

        HttpSession::set('auth.session_id', 'sid');
        $before = session_id();

        (new LogoutController($deps))->handle(new Request(Method::Post, '/logout', []));

        $this->assertNotSame($before, session_id());
    }
}
