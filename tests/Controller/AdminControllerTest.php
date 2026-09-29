<?php

use KittyShare\Controller\AdminController;
use KittyShare\Http\Method;
use KittyShare\Http\RedirectResponse;
use KittyShare\Http\Request;
use KittyShare\Http\Session as HttpSession;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Model\Session;
use KittyShare\Model\Share;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SessionRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FakeAdminSessionRepository implements SessionRepository
{
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
    }
}

final class AdminControllerTest extends TestCase
{
    private string $browseRoot = '';
    /** @var array<string,mixed> */
    private array $backupPost = [];
    /** @var array<string,mixed> */
    private array $backupGet = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupPost = $_POST;
        $this->backupGet = $_GET;
        $this->browseRoot = sys_get_temp_dir() . '/kittyshare-admin-' . bin2hex(random_bytes(4));
        mkdir($this->browseRoot . '/sub', 0777, true);
        file_put_contents($this->browseRoot . '/a.txt', 'a');
        putenv('KITTYSHARE_ROOT=' . $this->browseRoot);
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        HttpSession::remove('auth.session_id');
    }

    protected function tearDown(): void
    {
        $_POST = $this->backupPost;
        $_GET = $this->backupGet;
        HttpSession::remove('auth.session_id');
        putenv('KITTYSHARE_ROOT');
        putenv('KITTYSHARE_BASE_URL');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        $this->removeDir($this->browseRoot);
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        if ($dir === '' || !str_starts_with($dir, $tmp . DIRECTORY_SEPARATOR . 'kittyshare-')) {
            return;
        }
        if (is_link($dir)) {
            unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            if (is_file($dir)) {
                unlink($dir);
            }
            return;
        }
        foreach (scandir($dir) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $this->removeDir($dir . DIRECTORY_SEPARATOR . $e);
        }
        rmdir($dir);
    }

    private function session(): Session
    {
        return new Session('sid', new UserIdentity(1, 'admin'), new DateTimeImmutable('+1 hour'));
    }

    private function dependencies(?Session $current = null, ?Share $share = null, array $shares = []): Dependencies
    {
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $sessions = new FakeAdminSessionRepository($current);
        if ($current !== null) {
            HttpSession::set('auth.session_id', $current->id);
        } else {
            HttpSession::remove('auth.session_id');
        }
        $shareRepo = $this->createStub(KittyShare\Repository\ShareRepository::class);
        $shareRepo->method('find')->willReturn($share);
        $shareRepo->method('findByUser')->willReturn($shares);
        return new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $shareRepo,
            authenticationManager: new AuthenticationManager($users, $sessions),
        );
    }

    #[Test]
    public function unauthenticatedRedirectsToLogin(): void
    {
        $response = (new AdminController($this->dependencies(null)))->handle(new Request(Method::Get, '/admin', []));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function listRendersShares(): void
    {
        $deps = $this->dependencies($this->session(), null, []);

        $response = (new AdminController($deps))->handle(new Request(Method::Get, '/admin', []));


        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('admin/list', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function browseRendersDirectory(): void
    {
        $deps = $this->dependencies($this->session());

        $response = (new AdminController($deps))->handle(new Request(Method::Get, '/admin/browse', []));


        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('admin/browse', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function browseUnknownPathIs404(): void
    {
        $deps = $this->dependencies($this->session());

        $response = (new AdminController($deps))->handle(new Request(Method::Get, '/admin/browse/x', ['path' => 'nope']));


        $this->assertSame(404, (new ReflectionProperty($response, 'statusCode'))->getValue($response));
    }

    #[Test]
    public function detailUnknownShareIs404(): void
    {
        $deps = $this->dependencies($this->session(), null);

        $response = (new AdminController($deps))->handle(new Request(Method::Get, '/admin/shares/x', ['id' => 'x']));


        $this->assertSame(404, (new ReflectionProperty($response, 'statusCode'))->getValue($response));
    }

    #[Test]
    public function detailRendersShare(): void
    {
        $share = new Share('abc', new UserIdentity(1, 'admin'), $this->browseRoot, new DateTimeImmutable());
        $deps = $this->dependencies($this->session(), $share);

        $response = (new AdminController($deps))->handle(new Request(Method::Get, '/admin/shares/abc', ['id' => 'abc']));


        $this->assertSame('admin/share-detail', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function createValidPathRedirectsToDetail(): void
    {
        $share = new Share('new-id', new UserIdentity(1, 'admin'), $this->browseRoot, new DateTimeImmutable());
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $sessions = new FakeAdminSessionRepository($this->session());
        HttpSession::set('auth.session_id', 'sid');
        $shareRepo = $this->createStub(KittyShare\Repository\ShareRepository::class);
        $shareRepo->method('create')->willReturn($share);
        $deps = new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $shareRepo,
            authenticationManager: new AuthenticationManager($users, $sessions),
        );
        $_POST = ['filepath' => $this->browseRoot];

        $response = (new AdminController($deps))->handle(new Request(Method::Post, '/admin/shares', []));


        $this->assertSame('/admin/shares/new-id', (new ReflectionProperty($response, 'url'))->getValue($response));
    }

    #[Test]
    public function createOutsideRootIs404(): void
    {
        $deps = $this->dependencies($this->session());
        $_POST = ['filepath' => '/etc/passwd'];

        $response = (new AdminController($deps))->handle(new Request(Method::Post, '/admin/shares', []));


        $this->assertSame(404, (new ReflectionProperty($response, 'statusCode'))->getValue($response));
    }
}
