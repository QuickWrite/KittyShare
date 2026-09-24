<?php

namespace KittyShare\Database;

use KittyShare\Database\Migration\Migration;
use KittyShare\Repository\SQLiteDBVersionRepository;
use PDO;
use Exception;

use function count;
use function assert;
use function sort;

/**
 * Applies pending SQLite schema migrations.
 */
final class SQLiteMigrator
{
    private const MIGRATION_NAMESPACE = 'KittyShare\\Database\\Migration\\sqlite';

    /**
     * Applies all pending migrations and returns the versions that were applied.
     *
     * Each migration runs in its own immediate transaction.
     *
     * @param PDO $pdo An open connection to the SQLite database.
     * @return list<int> The applied migration versions, in order (empty when up to date).
     */
    public static function migrate(PDO $pdo): array
    {
        $migrationsDir = self::migrationsDir();
        $versions = new SQLiteDBVersionRepository($pdo);
        $version = $versions->currentVersion();
        $applied = [];

        foreach (self::migrationFiles($migrationsDir) as $file) {
            preg_match('/V(\d+)_/', basename($file), $m);
            assert(count($m) === 2, 'The matched values should not be empty');

            $fileVersion = (int) $m[1];

            if ($fileVersion <= $version) {
                continue;
            }

            if ($fileVersion > SQLiteDBVersionRepository::EXPECTED_VERSION) {
                throw new MigrationMismatchException($fileVersion, SQLiteDBVersionRepository::EXPECTED_VERSION);
            }

            require_once $file;
            $className = self::MIGRATION_NAMESPACE . "\\MigrationV{$fileVersion}";

            if (!is_subclass_of($className, Migration::class)) {
                continue;
            }

            $pdo->exec('BEGIN IMMEDIATE TRANSACTION');
            try {
                $className::up($pdo);
                $pdo->exec("PRAGMA user_version = {$fileVersion}");
                $pdo->exec('COMMIT');
            } catch (Exception $e) {
                $pdo->exec('ROLLBACK');
                throw $e;
            }

            $version = $versions->currentVersion();
            $applied[] = $fileVersion;
        }

        return $applied;
    }

    /**
     * @return list<string> Sorted migration file paths.
     */
    private static function migrationFiles(string $migrationsDir): array
    {
        $files = glob("{$migrationsDir}/V*_*.php");

        if (!$files) {
            return [];
        }

        // Ensure that the items are sorted by their "natural" sorting order (e.g. 9 before 10).
        // ["V10_a.php", "V9_b.php", "V11_c.php"] => ["V9_b.php", "V10_a.php", "V11_c.php"]
        sort($files, SORT_NATURAL);

        return $files;
    }

    private static function migrationsDir(): string
    {
        return __DIR__ . '/Migration/sqlite';
    }
}
