<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Model\UserIdentity;
use KittyShare\Repository\SQLiteShareRepository;
use KittyShare\Repository\SQLiteUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteShareRepositoryTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-share-repo-');
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

        $repos = new SQLiteShareRepository($this->pdo);
        $share = $repos->create($identity, '/tmp');

        $this->assertNotNull($repos->find($share->id));
        $repos->delete($share);

        $this->assertNull($repos->find($share->id));
    }

    #[Test]
    public function findByUserOnlyReturnsOwnShares(): void
    {
        $users = new SQLiteUserRepository($this->pdo);
        $alice = $users->createUser('alice', 'pw');
        $bob = $users->createUser('bob', 'pw');

        $repos = new SQLiteShareRepository($this->pdo);
        $repos->create(new UserIdentity($alice->userId, $alice->username), '/tmp/a');
        $repos->create(new UserIdentity($bob->userId, $bob->username), '/tmp/b');
        $list = $repos->findByUser(new UserIdentity($alice->userId, $alice->username));

        $this->assertCount(1, $list);
        $this->assertSame('/tmp/a', $list[0]->filepath);
    }

    #[Test]
    public function saveRevocation(): void
    {
        $users = new SQLiteUserRepository($this->pdo);
        $user = $users->createUser('alice', 'pw');

        $repos = new SQLiteShareRepository($this->pdo);
        $share = $repos->create(new UserIdentity($user->userId, $user->username), '/tmp');
        $repos->save($share->revoke());
        $fresh = $repos->find($share->id);

        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->isRevoked());
    }
}
