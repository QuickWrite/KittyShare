<?php

use KittyShare\Database\SQLiteDatabase;
use KittyShare\Database\SQLiteMigrator;
use KittyShare\Repository\SQLiteDBVersionRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteDBVersionRepositoryTest extends TestCase
{
    private string $dbPath = '';
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-version-');
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
    public function currentVersionMatchesExpected(): void
    {
        $repo = new SQLiteDBVersionRepository($this->pdo);

        $this->assertSame(SQLiteDBVersionRepository::EXPECTED_VERSION, $repo->currentVersion());
        $this->assertTrue($repo->isValid());
    }
}
