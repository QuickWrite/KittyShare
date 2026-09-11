<?php

namespace KittyShare\Repository;

use KittyShare\Repository\Migration\Migration;
use PDO;
use Exception;

use function count;
use function assert;

final class SQLiteDatabase
{
    private static string $migration_namespace = 'KittyShare\\Repository\\Migration\\sqlite';
    private PDO $instance;

    public function __construct(string $path)
    {
        $this->instance = self::loadConnection($path);
    }

    public function getInstance(): PDO
    {
        return $this->instance;
    }

    private static function loadConnection(string $path): PDO
    {
        $instance = new PDO("sqlite:$path");
        $instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $instance->exec('PRAGMA journal_mode=WAL');
        $instance->exec('PRAGMA foreign_keys = ON');
        $instance->exec('PRAGMA busy_timeout = 3000');

        self::migrateSchema($instance);

        return $instance;
    }

    private static function migrateSchema(PDO $pdo): void
    {
        $migrationsDir = __DIR__ . '/Migration/sqlite';

        $versionQuery = $pdo->query('PRAGMA user_version');
        assert($versionQuery !== false, "Could not execute query to check version.");

        $version = (int) $versionQuery->fetchColumn();

        $files = glob("$migrationsDir/V*_*.php");
        
        // Currently it should only fail silently. Maybe this should change later on.
        if (!$files) {
            $files = [];
        }

        sort($files);

        foreach ($files as $file) {
            preg_match('/V(\d+)_/', basename($file), $m);
            assert(count($m) === 2, "The matched values should not be empty");

            $fileVersion = (int) $m[1];

            if ($fileVersion <= $version) {
                continue;
            }

            require_once $file;
            $className = self::$migration_namespace . "\\MigrationV{$fileVersion}";

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

            $versionQuery = $pdo->query('PRAGMA user_version');
            assert($versionQuery !== false, "Could not execute query to check version.");
            $version = (int) $versionQuery->fetchColumn();
        }
    }
}
