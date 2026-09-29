<?php

use KittyShare\Controller\LoginController;
use KittyShare\Http\Method;
use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Request;
use KittyShare\Http\Session as HttpSession;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Model\Session;
use KittyShare\Model\User;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SessionRepository;
use KittyShare\Repository\SetupRepository;
use KittyShare\Repository\ShareRepository;
use KittyShare\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakeLoginUserRepository implements UserRepository
{
    public function __construct(private ?User $user = null)
    {
    }

    public function getUser(string $username): ?User
    {
        return $this->user !== null && $username === $this->user->username ? $this->user : null;
    }

    public function createUser(string $username, string $password): User
    {
        throw new RuntimeException('not used');
    }
}

final class FakeLoginSessionRepository implements SessionRepository
{
    public function __construct(private ?Session $current = null)
    {
    }

    public function create(UserIdentity $user): Session
    {
        return new Session('new-id', $user, new DateTimeImmutable('+1 hour'));
    }

    public function find(string $id): ?Session
    {
        return $this->current;
    }

    public function delete(Session $session): void
    {
    }
}

final class FakeLoginSetupRepository implements SetupRepository
{
    public function __construct(private bool $required)
    {
    }

    public function setupRequired(): bool
    {
        return $this->required;
    }
}

final class LoginControllerTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $backupPost = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPost = $_POST;
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('auth.session_id', 'login.form.errors', 'login.form.values');
    }

    protected function tearDown(): void
    {
        $_POST = $this->backupPost;
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('auth.session_id', 'login.form.errors', 'login.form.values');
        parent::tearDown();
    }

    private function dependencies(bool $setupRequired, ?User $user = null, ?Session $current = null): Dependencies
    {
        $users = new FakeLoginUserRepository($user);
        $sessions = new FakeLoginSessionRepository($current);
        return new Dependencies(
            userRepository: $users,
            setupRepository: new FakeLoginSetupRepository($setupRequired),
            sessionRepository: $sessions,
            shareRepository: $this->createStub(ShareRepository::class),
            authenticationManager: new AuthenticationManager($users, $sessions),
        );
    }

    private function user(): User
    {
        return new User(1, 'admin', password_hash('pw', PASSWORD_BCRYPT, ['cost' => 4]));
    }

    #[Test]
    public function redirectsToSetupWhenRequired(): void
    {
        $response = (new LoginController($this->dependencies(true)))->handle(new Request(Method::Get, '/login', []));

        $this->assertSame('/setup', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function redirectsToAdminWhenAlreadyAuthenticated(): void
    {
        $session = new Session('id', new UserIdentity(1, 'admin'), new DateTimeImmutable('+1 hour'));
        HttpSession::set('auth.session_id', 'id');
        $response = (new LoginController($this->dependencies(false, $this->user(), $session)))->handle(new Request(Method::Get, '/login', []));

        $this->assertSame('/admin', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function getRendersLoginForm(): void
    {
        $response = (new LoginController($this->dependencies(false)))->handle(new Request(Method::Get, '/login', []));

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('auth/login', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function postWithEmptyFieldsRedirectsBack(): void
    {
        $_POST = ['username' => '', 'password' => ''];
        $response = (new LoginController($this->dependencies(false)))->handle(new Request(Method::Post, '/login', []));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function postWithInvalidCredentialsRedirectsBack(): void
    {
        $_POST = ['username' => 'admin', 'password' => 'wrong'];
        $response = (new LoginController($this->dependencies(false, $this->user())))->handle(new Request(Method::Post, '/login', []));

        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
        $this->assertSame(['credentials' => 'invalid'], HttpSession::get('login.form.errors'));
    }

    #[Test]
    public function postWithValidCredentialsRedirectsToAdmin(): void
    {
        $_POST = ['username' => 'admin', 'password' => 'pw'];
        $response = (new LoginController($this->dependencies(false, $this->user())))->handle(new Request(Method::Post, '/login', []));

        $this->assertSame('/admin', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function loginRequiresExactPasswordMatch(): void
    {
        $_POST = ['username' => 'admin', 'password' => '  pw  '];
        $response = (new LoginController($this->dependencies(false, $this->user())))->handle(new Request(Method::Post, '/login', []));

        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
    }
}
