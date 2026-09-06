<?php

namespace KittyShare\Repository\Migration\sqlite;

use KittyShare\Repository\Migration\Migration;
use PDO;
use Override;

class MigrationV1 implements Migration
{
    #[Override]
    public static function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                username     TEXT NOT NULL UNIQUE,
                passwordHash TEXT NOT NULL
            );
        ');
    }
}
