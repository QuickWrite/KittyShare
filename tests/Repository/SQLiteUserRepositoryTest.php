<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SQLiteUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteUserRepositoryTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-user-');
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
    public function createAndFindUser(): void
    {
        $repo = new SQLiteUserRepository($this->pdo);
        $created = $repo->createUser('alice', 'pw');

        $this->assertSame('alice', $created->username);
        $found = $repo->getUser('alice');

        $this->assertNotNull($found);
        $this->assertTrue(password_verify('pw', $found->passwordHash));
    }

    #[Test]
    public function unknownUserReturnsNull(): void
    {
        $this->assertNull((new SQLiteUserRepository($this->pdo))->getUser('ghost'));
    }

    #[Test]
    public function duplicateUsernameThrows(): void
    {
        $repo = new SQLiteUserRepository($this->pdo);
        $repo->createUser('alice', 'pw');

        $this->expectException(PDOException::class);
        $repo->createUser('alice', 'other');
    }
}
