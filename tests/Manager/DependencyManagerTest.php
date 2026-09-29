<?php

use KittyShare\Database\DatabaseVersionException;
use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Http\Session as HttpSession;
use KittyShare\Manager\AuthenticationManager;
use KittyShare\Manager\ConfigManager;
use KittyShare\Manager\DependencyManager;
use KittyShare\Repository\SQLiteSessionRepository;
use KittyShare\Repository\SQLiteSetupRepository;
use KittyShare\Repository\SQLiteShareRepository;
use KittyShare\Repository\SQLiteUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DependencyManagerTest extends TestCase
{
    private const SESSION_KEY = 'auth.session_id';

    /** @var list<string> */
    private array $tempFiles = [];

    /** @var array<string, string|false> */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['KITTYSHARE_DATABASE_PATH', 'KITTYSHARE_SESSION_LIFETIME'] as $name) {
            $this->originalEnv[$name] = getenv($name);
            putenv($name);
        }

        $this->resetManagers();
        HttpSession::remove(self::SESSION_KEY);
    }

    protected function tearDown(): void
    {
        HttpSession::remove(self::SESSION_KEY);

        foreach ($this->originalEnv as $name => $value) {
            if ($value === false) {
                putenv($name);
            } else {
                putenv($name . '=' . $value);
            }
        }

        $this->resetManagers();

        foreach ($this->tempFiles as $path) {
            foreach ([$path, $path . '-wal', $path . '-shm', $path . '-journal'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }

    private function resetManagers(): void
    {
        (new ReflectionProperty(ConfigManager::class, 'config'))->setValue(null, null);
        (new ReflectionProperty(DependencyManager::class, 'dependencies'))->setValue(null, null);
    }

    private function configure(array $env): void
    {
        foreach ($env as $name => $value) {
            putenv($name . '=' . $value);
        }

        $this->resetManagers();
    }

    private function createMigratedDatabase(): string
    {
        $base = tempnam(sys_get_temp_dir(), 'kittyshare-dep-');
        assert(is_string($base));
        unlink($base);

        $path = $base . '.sqlite';
        $this->tempFiles[] = $path;

        $pdo = SQLiteDatabase::connect($path);
        SQLiteMigrator::migrate($pdo);
        $pdo = null;

        return $path;
    }


    private function createUnmigratedDatabase(): string
    {
        $base = tempnam(sys_get_temp_dir(), 'kittyshare-dep-');
        assert(is_string($base));
        unlink($base);

        $path = $base . '.sqlite';
        $this->tempFiles[] = $path;

        $pdo = SQLiteDatabase::connect($path);
        $pdo = null;

        return $path;
    }

    #[Test]
    public function returnsSameInstanceWhileCached(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $this->assertSame(DependencyManager::get(), DependencyManager::get());
    }

    #[Test]
    public function exposesAllRepositoriesWithCorrectTypes(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $dependencies = DependencyManager::get();

        $this->assertInstanceOf(SQLiteUserRepository::class, $dependencies->userRepository);
        $this->assertInstanceOf(SQLiteSetupRepository::class, $dependencies->setupRepository);
        $this->assertInstanceOf(SQLiteSessionRepository::class, $dependencies->sessionRepository);
        $this->assertInstanceOf(SQLiteShareRepository::class, $dependencies->shareRepository);
        $this->assertInstanceOf(AuthenticationManager::class, $dependencies->authenticationManager);
    }

    #[Test]
    public function authenticationManagerSharesManagedRepositories(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $dependencies = DependencyManager::get();

        $userProperty = new ReflectionProperty(AuthenticationManager::class, 'userRepository');
        $sessionProperty = new ReflectionProperty(AuthenticationManager::class, 'sessionRepository');

        $this->assertSame($dependencies->userRepository, $userProperty->getValue($dependencies->authenticationManager));
        $this->assertSame($dependencies->sessionRepository, $sessionProperty->getValue($dependencies->authenticationManager));
    }

    #[Test]
    public function honoursSessionLifetimeFromConfig(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path, 'KITTYSHARE_SESSION_LIFETIME' => '1234']);

        $lifetime = new ReflectionProperty(SQLiteSessionRepository::class, 'sessionLifetime');

        $this->assertSame(1234, $lifetime->getValue(DependencyManager::get()->sessionRepository));
    }

    #[Test]
    public function usesDefaultSessionLifetimeWhenUnset(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $lifetime = new ReflectionProperty(SQLiteSessionRepository::class, 'sessionLifetime');

        $this->assertSame(60 * 60 * 24 * 30, $lifetime->getValue(DependencyManager::get()->sessionRepository));
    }

    #[Test]
    public function writesToConfiguredDatabaseFile(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        DependencyManager::get()->userRepository->createUser('alice', 's3cret');

        $check = SQLiteDatabase::connect($path);
        $count = $check->query("SELECT COUNT(*) FROM users WHERE username = 'alice'")->fetchColumn();
        $check = null;

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function rejectsDatabaseWithInvalidVersion(): void
    {
        $path = $this->createUnmigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $this->expectException(DatabaseVersionException::class);

        DependencyManager::get();
    }

    #[Test]
    public function freshDatabaseRefusesWithMigrationHint(): void
    {
        $path = $this->createUnmigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        try {
            DependencyManager::get();

            $this->fail('fresh database must refuse to start');
        } catch (DatabaseVersionException $e) {

            $this->assertStringContainsString('bin/migrate', $e->getMessage());
        }
    }

    #[Test]
    public function managedRepositoriesWorkEndToEnd(): void
    {
        $path = $this->createMigratedDatabase();
        $this->configure(['KITTYSHARE_DATABASE_PATH' => $path]);

        $dependencies = DependencyManager::get();
        $dependencies->userRepository->createUser('bob', 's3cret');

        $session = $dependencies->authenticationManager->login('bob', 's3cret');

        $this->assertNotNull($session);
        $this->assertSame('bob', $session->user->username);
        $this->assertSame($session->id, HttpSession::get(self::SESSION_KEY));

        $current = $dependencies->authenticationManager->currentSession();

        $this->assertNotNull($current);
        $this->assertSame($session->id, $current->id);
        $this->assertSame('bob', $current->user->username);

        $dependencies->authenticationManager->logout();

        $this->assertNull(HttpSession::get(self::SESSION_KEY));
        $this->assertNull($dependencies->authenticationManager->currentSession());
    }
}
