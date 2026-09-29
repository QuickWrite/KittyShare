<?php

use KittyShare\Http\Session as HttpSession;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Model\Session;
use KittyShare\Model\User;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SessionRepository;
use KittyShare\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationManagerTest extends TestCase
{
    private const SESSION_KEY = 'auth.session_id';

    protected function setUp(): void
    {
        parent::setUp();

        HttpSession::remove(self::SESSION_KEY);
    }

    protected function tearDown(): void
    {
        HttpSession::remove(self::SESSION_KEY);

        parent::tearDown();
    }

    private function makeUser(string $username = 'test', string $password = 'correct-password'): User
    {
        return new User(
            1,
            $username,
            password_hash($password, PASSWORD_BCRYPT, ['cost' => 4])
        );
    }

    private function makeSession(string $id = 'session-id', ?UserIdentity $user = null, ?DateTimeImmutable $expiresAt = null): Session
    {
        return new Session(
            $id,
            $user ?? new UserIdentity(1, 'test'),
            $expiresAt ?? new DateTimeImmutable('+1 hour')
        );
    }

    private function stubUserRepository(?User $user): UserRepository
    {
        $repo = $this->createStub(UserRepository::class);
        $repo->method('getUser')->willReturn($user);

        return $repo;
    }

    private function stubSessionRepository(?Session $sessionToCreate = null): SessionRepository
    {
        $repo = $this->createStub(SessionRepository::class);

        if ($sessionToCreate !== null) {
            $repo->method('create')->willReturn($sessionToCreate);
        }

        return $repo;
    }

    #[Test]
    public function loginSuccessful(): void
    {
        $user = $this->makeUser();
        $session = $this->makeSession('new-session-id', new UserIdentity(1, 'test'));

        $manager = new AuthenticationManager(
            $this->stubUserRepository($user),
            $this->stubSessionRepository($session)
        );

        $result = $manager->login('test', 'correct-password');

        $this->assertSame($session, $result);
        $this->assertSame('new-session-id', HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginReturnsNullForUnknownUser(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('create');

        $manager = new AuthenticationManager(
            $this->stubUserRepository(null),
            $sessionRepository
        );

        $this->assertNull($manager->login('nobody', 'whatever'));
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginReturnsNullOnWrongPassword(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('create');

        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $sessionRepository
        );

        $this->assertNull($manager->login('test', 'wrong-password'));
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginReturnsNullOnEmptyPassword(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('create');

        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $sessionRepository
        );

        $this->assertNull($manager->login('test', ''));
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginDoesNotRegenerateSessionIdOnFailure(): void
    {
        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $this->stubSessionRepository()
        );

        $before = session_id();
        $manager->login('test', 'wrong-password');

        $this->assertSame($before, session_id());
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginRegeneratesSessionIdToPreventFixation(): void
    {
        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $this->stubSessionRepository($this->makeSession('new-session-id'))
        );

        $before = session_id();

        $manager->login('test', 'correct-password');

        $this->assertNotSame($before, session_id());
    }

    #[Test]
    public function loginPassesAuthenticatedUserToSessionRepository(): void
    {
        $user = $this->makeUser();
        $session = $this->makeSession('new-session-id');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('getUser')->willReturn($user);

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('create')
            ->with($this->identicalTo($user))
            ->willReturn($session);

        $manager = new AuthenticationManager($userRepository, $sessionRepository);

        $manager->login('test', 'correct-password');
    }

    #[Test]
    public function loginFailurePreservesPreExistingSession(): void
    {
        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $this->stubSessionRepository()
        );

        HttpSession::set(self::SESSION_KEY, 'old-session-id');

        $this->assertNull($manager->login('test', 'wrong-password'));

        $this->assertSame('old-session-id', HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function loginOverwritesPreExistingSessionId(): void
    {
        $session = $this->makeSession('brand-new-id');

        $manager = new AuthenticationManager(
            $this->stubUserRepository($this->makeUser()),
            $this->stubSessionRepository($session)
        );

        HttpSession::set(self::SESSION_KEY, 'stale-id');

        $result = $manager->login('test', 'correct-password');

        $this->assertSame($session, $result);
        $this->assertSame('brand-new-id', HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function currentSessionReturnsNullWhenNothingStored(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('find');

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        $this->assertNull($manager->currentSession());
    }

    #[Test]
    public function currentSessionReturnsNullForNonStringIdWithoutQueryingRepository(): void
    {
        HttpSession::set(self::SESSION_KEY, 12345);

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('find');

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        $this->assertNull($manager->currentSession());
    }

    #[Test]
    public function currentSessionReturnsValidSession(): void
    {
        $session = $this->makeSession('valid-id');

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('valid-id')
            ->willReturn($session);

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'valid-id');

        $this->assertSame($session, $manager->currentSession());
        $this->assertSame('valid-id', HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function currentSessionClearsKeyWhenSessionNotFound(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('ghost-id')
            ->willReturn(null);

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'ghost-id');

        $this->assertNull($manager->currentSession());
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function currentSessionClearsKeyWhenSessionExpired(): void
    {
        $expired = $this->makeSession('expired-id', null, new DateTimeImmutable('-1 hour'));

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('expired-id')
            ->willReturn($expired);

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'expired-id');

        $this->assertNull($manager->currentSession());
        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function logoutDeletesSessionAndClearsKey(): void
    {
        $session = $this->makeSession('logout-id');

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('logout-id')
            ->willReturn($session);
        $sessionRepository->expects($this->once())->method('delete')->with($this->identicalTo($session));

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'logout-id');

        $manager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function logoutWithoutStoredIdDoesNotTouchRepository(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('find');
        $sessionRepository->expects($this->never())->method('delete');

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        $manager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function logoutWithUnknownIdClearsKeyWithoutDelete(): void
    {
        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('ghost-id')
            ->willReturn(null);
        $sessionRepository->expects($this->never())->method('delete');

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'ghost-id');

        $manager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function logoutWithNonStringIdClearsKeyWithoutRepoInteraction(): void
    {
        HttpSession::set(self::SESSION_KEY, ['not', 'a', 'string']);

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->never())->method('find');
        $sessionRepository->expects($this->never())->method('delete');

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        $manager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }

    #[Test]
    public function logoutIsIdempotent(): void
    {
        $session = $this->makeSession('logout-id');

        $sessionRepository = $this->createMock(SessionRepository::class);
        $sessionRepository->expects($this->once())
            ->method('find')
            ->with('logout-id')
            ->willReturn($session);
        $sessionRepository->expects($this->once())->method('delete')->with($session);

        $manager = new AuthenticationManager(
            $this->createStub(UserRepository::class),
            $sessionRepository
        );

        HttpSession::set(self::SESSION_KEY, 'logout-id');

        $manager->logout();
        $manager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
    }
}
