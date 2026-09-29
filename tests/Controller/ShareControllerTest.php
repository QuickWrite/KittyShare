<?php

use KittyShare\Controller\ShareController;
use KittyShare\Http\FileResponse;
use KittyShare\Http\Method;
use KittyShare\Http\Request;
use KittyShare\Http\TemplateResponse;
use KittyShare\Manager\ConfigManager;
use KittyShare\Model\Dependencies;
use KittyShare\Model\Share;
use KittyShare\Model\UserIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShareControllerTest extends TestCase
{
    private string $base = '';
    private string $shareDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        putenv('KITTYSHARE_BASE_URL');
        putenv('KITTYSHARE_SHOW_DOTFILES');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        $this->base = sys_get_temp_dir() . '/kittyshare-share-' . bin2hex(random_bytes(4));
        mkdir($this->base . '/share/sub', 0777, true);
        file_put_contents($this->base . '/share/hello.txt', 'hello');
        file_put_contents($this->base . '/share/sub/nested.txt', 'nested');
        $this->shareDir = (string) realpath($this->base . '/share');
    }

    protected function tearDown(): void
    {
        putenv('KITTYSHARE_BASE_URL');
        putenv('KITTYSHARE_SHOW_DOTFILES');
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        $this->removeDir($this->base);
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

    private function dependencies(?Share $share): Dependencies
    {
        $shares = $this->createStub(KittyShare\Repository\ShareRepository::class);
        $shares->method('find')->willReturn($share);
        $users = $this->createStub(KittyShare\Repository\UserRepository::class);
        $sessions = $this->createStub(KittyShare\Repository\SessionRepository::class);
        return new Dependencies(
            userRepository: $users,
            setupRepository: $this->createStub(KittyShare\Repository\SetupRepository::class),
            sessionRepository: $sessions,
            shareRepository: $shares,
            authenticationManager: new KittyShare\Manager\AuthenticationManager($users, $sessions),
        );
    }

    private function share(string $filepath): Share
    {
        return new Share('abc', new UserIdentity(1, 'admin'), $filepath, new DateTimeImmutable());
    }

    #[Test]
    public function unknownShareIsNotFound(): void
    {
        $response = (new ShareController($this->dependencies(null)))->handle(new Request(Method::Get, '/share/x', ['id' => 'x']));

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('share/not-found', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function revokedShareIsNotFound(): void
    {
        $share = $this->share($this->shareDir)->revoke();
        $response = (new ShareController($this->dependencies($share)))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc']));

        $this->assertSame('share/not-found', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function directoryRootRendersListing(): void
    {
        $response = (new ShareController($this->dependencies($this->share($this->shareDir))))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc']));

        $this->assertSame('share/directory', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function fileInShareIsServed(): void
    {
        $response = (new ShareController($this->dependencies($this->share($this->shareDir))))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc', 'path' => 'hello.txt']));

        $this->assertInstanceOf(FileResponse::class, $response);
    }

    #[Test]
    public function missingSubpathIsNotFound(): void
    {
        $response = (new ShareController($this->dependencies($this->share($this->shareDir))))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc', 'path' => 'nope.txt']));

        $this->assertSame('share/not-found', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function hiddenDotfileDirectAccessIsNotFound(): void
    {
        file_put_contents($this->shareDir . '/.secret', 'hidden');
        $response = (new ShareController($this->dependencies($this->share($this->shareDir))))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc', 'path' => '.secret']));

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertSame('share/not-found', (new ReflectionProperty($response, 'template'))->getValue($response));
    }

    #[Test]
    public function shareOutsideCurrentRootIsNotFound(): void
    {
        $outside = sys_get_temp_dir() . '/kittyshare-share-outside-' . bin2hex(random_bytes(4));
        mkdir($outside, 0777, true);
        file_put_contents($outside . '/file.txt', 'x');
        putenv('KITTYSHARE_ROOT=' . $this->base);
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        try {
            $response = (new ShareController($this->dependencies($this->share($outside))))->handle(new Request(Method::Get, '/share/x', ['id' => 'abc']));
            $this->assertInstanceOf(TemplateResponse::class, $response);
            $this->assertSame('share/not-found', (new ReflectionProperty($response, 'template'))->getValue($response));
        } finally {
            putenv('KITTYSHARE_ROOT');
            (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
            $this->removeDir($outside);
        }
    }
}
