<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SQLiteSessionRepository;
use KittyShare\Repository\SQLiteUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteSessionRepositoryTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-session-');
        assert(is_string($base));
        unlink($base);

        $this->dbPath = $base . '.sqlite';
        $this->pdo = SQLiteDatabase::connect($this->dbPath);

        SQLiteMigrator::migrate($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = new PDO('sqlite::memory:');

        foreach ([$this->dbPath, $this->dbPath . '-wal', $this->dbPath . '-shm', $this->dbPath . '-journal'] as $f) {
            if (is_file($f)) {
                unlink($f);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function createFindAndDelete(): void
    {
        $users = new SQLiteUserRepository($this->pdo);
        $user = $users->createUser('alice', 'pw');
        $identity = new UserIdentity($user->userId, $user->username);

        $repos = new SQLiteSessionRepository($this->pdo, 3600);
        $session = $repos->create($identity);

        $this->assertFalse($session->isExpired());
        $this->assertNotNull($repos->find($session->id));
        $repos->delete($session);

        $this->assertNull($repos->find($session->id));
    }

    #[Test]
    public function unknownSessionReturnsNull(): void
    {
        $this->assertNull((new SQLiteSessionRepository($this->pdo))->find('ghost'));
    }
}
