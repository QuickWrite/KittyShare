<?php

namespace KittyShare\Repository;

use KittyShare\Repository\Migration\Migration;
use PDO;
use Exception;

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
        $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();

        $files = glob("$migrationsDir/V*_*.php");
        sort($files);

        foreach ($files as $file) {
            preg_match('/V(\d+)_/', basename($file), $m);
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
            }

            $version = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
        }
    }
}
