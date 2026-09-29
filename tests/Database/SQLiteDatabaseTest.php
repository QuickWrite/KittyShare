<?php

use KittyShare\Database\SQLiteDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SQLiteDatabaseTest extends TestCase
{
    private string $dbPath = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'kittyshare-db-');
        assert(is_string($base));
        unlink($base);

        $this->dbPath = $base . '.sqlite';
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ([$this->dbPath, $this->dbPath . '-wal', $this->dbPath . '-shm', $this->dbPath . '-journal'] as $f) {
            if ($f !== '' && is_file($f)) {
                unlink($f);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function connectCreatesUsableConnection(): void
    {
        $pdo = SQLiteDatabase::connect($this->dbPath);

        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(1, (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn());
        $this->assertFileExists($this->dbPath);

        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $pdo->exec("INSERT INTO test (value) VALUES ('hello')");

        $this->assertSame(
            'hello',
            $pdo->query('SELECT value FROM test WHERE id = 1')->fetchColumn()
        );
    }

    #[Test]
    public function foreignKeysAreEnabledAndEnforced(): void
    {
        $pdo = SQLiteDatabase::connect($this->dbPath);

        $this->assertSame(
            1,
            (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn()
        );

        $pdo->exec('
            CREATE TABLE parent (
                id INTEGER PRIMARY KEY
            )
        ');

        $pdo->exec('
            CREATE TABLE child (
                id INTEGER PRIMARY KEY,
                parent_id INTEGER NOT NULL,
                FOREIGN KEY (parent_id) REFERENCES parent(id)
            )
        ');

        $this->expectException(PDOException::class);

        $pdo->exec('
            INSERT INTO child (parent_id)
            VALUES (999)
        ');
    }
}
