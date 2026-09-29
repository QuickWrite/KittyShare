<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Repository\SQLiteSetupRepository;
use KittyShare\Repository\SQLiteUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteSetupRepositoryTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-setup-repo-');
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
    public function setupRequiredUntilFirstUser(): void
    {
        $this->assertTrue((new SQLiteSetupRepository($this->pdo))->setupRequired());
        (new SQLiteUserRepository($this->pdo))->createUser('admin', 'pw');

        $this->assertFalse((new SQLiteSetupRepository($this->pdo))->setupRequired());
    }
}
