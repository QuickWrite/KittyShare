<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Repository\SQLiteDBVersionRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteMigratorTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-migrate-');
        assert(is_string($base));
        unlink($base);

        $this->dbPath = $base . '.sqlite';
        $this->pdo = SQLiteDatabase::connect($this->dbPath);
    }

    #[Override]
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
    public function migrateAppliesAllVersions(): void
    {
        $applied = SQLiteMigrator::migrate($this->pdo);

        $this->assertSame([1, 2, 3], $applied);
        $this->assertSame(3, (new SQLiteDBVersionRepository($this->pdo))->currentVersion());
    }

    #[Test]
    public function migrateIsIdempotent(): void
    {
        SQLiteMigrator::migrate($this->pdo);

        $this->assertSame([], SQLiteMigrator::migrate($this->pdo));
    }

    #[Test]
    public function migrateRefusesDatabaseNewerThanExpected(): void
    {
        $this->pdo->exec('PRAGMA user_version = 99');

        $this->expectException(KittyShare\Database\MigrationMismatchException::class);
        SQLiteMigrator::migrate($this->pdo);
    }
}
