<?php

use KittyShare\Controller\SetupController;
use KittyShare\Http\Method;
use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Request;
use KittyShare\Http\Session as HttpSession;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Model\User;
use KittyShare\Repository\SessionRepository;
use KittyShare\Repository\SetupRepository;
use KittyShare\Repository\ShareRepository;
use KittyShare\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakeSetupUserRepository implements UserRepository
{
    public ?User $created = null;
    public int $createCalls = 0;
    public ?string $createdPassword = null;

    public function getUser(string $username): ?User
    {
        return null;
    }

    public function createUser(string $username, string $password): User
    {
        $this->createCalls++;
        $this->createdPassword = $password;
        $this->created = new User(1, $username, 'hash');
        return $this->created;
    }
}

final class FakeSetupSetupRepository implements SetupRepository
{
    public function __construct(private bool $required)
    {
    }

    public function setupRequired(): bool
    {
        return $this->required;
    }
}

final class SetupControllerTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $backupPost = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPost = $_POST;
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('setup.form.errors', 'setup.form.values');
    }

    protected function tearDown(): void
    {
        $_POST = $this->backupPost;
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('setup.form.errors', 'setup.form.values');
        parent::tearDown();
    }

    private function dependencies(bool $setupRequired, ?FakeSetupUserRepository $users = null): array
    {
        $users ??= new FakeSetupUserRepository();
        $sessions = $this->createStub(SessionRepository::class);
        return [$users, new Dependencies(
            userRepository: $users,
            setupRepository: new FakeSetupSetupRepository($setupRequired),
            sessionRepository: $sessions,
            shareRepository: $this->createStub(ShareRepository::class),
            authenticationManager: new AuthenticationManager($users, $sessions),
        )];
    }

    #[Test]
    public function redirectsToLoginWhenSetupNotRequired(): void
    {
        [, $deps] = $this->dependencies(false);

        $response = (new SetupController($deps))->handle(new Request(Method::Get, '/setup', []));


        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function getRendersForm(): void
    {
        [, $deps] = $this->dependencies(true);

        $response = (new SetupController($deps))->handle(new Request(Method::Get, '/setup', []));


        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('setup/form', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function postWithEmptyFieldsRedirectsBack(): void
    {
        [$users, $deps] = $this->dependencies(true);
        $_POST = ['username' => '', 'password' => ''];

        $response = (new SetupController($deps))->handle(new Request(Method::Post, '/setup', []));


        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/setup', (new ReflectionProperty($response, 'url'))->getValue($response));
        $this->assertSame(0, $users->createCalls);
    }

    #[Test]
    public function postCreatesUserAndRedirectsToLogin(): void
    {
        [$users, $deps] = $this->dependencies(true);
        $_POST = ['username' => 'admin', 'password' => 's3cret'];

        $response = (new SetupController($deps))->handle(new Request(Method::Post, '/setup', []));


        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
        $this->assertSame(1, $users->createCalls);
        $this->assertSame('admin', $users->created?->username);
    }

    #[Test]
    public function secondSetupDoesNotError(): void
    {
        [$users, $deps] = $this->dependencies(false);
        $_POST = ['username' => 'second', 'password' => 'pw2'];

        $response = (new SetupController($deps))->handle(new Request(Method::Post, '/setup', []));


        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
        $this->assertSame(0, $users->createCalls);
    }

    #[Test]
    public function loginRequiresExactPasswordMatch(): void
    {
        // Passwords must be stored exactly as entered; trimming them changes
        // the credential and breaks exact-match login.
        [$users, $deps] = $this->dependencies(true);
        $_POST = ['username' => 'admin', 'password' => '  s3cret  '];

        (new SetupController($deps))->handle(new Request(Method::Post, '/setup', []));


        $this->assertSame('  s3cret  ', $users->createdPassword);
    }
}
