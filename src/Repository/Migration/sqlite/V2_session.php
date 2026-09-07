<?php

namespace KittyShare\Repository\Migration\sqlite;

use KittyShare\Repository\Migration\Migration;
use PDO;
use Override;

class MigrationV2 implements Migration
{
    #[Override]
    public static function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS sessions (
                id         TEXT PRIMARY KEY,
                userId     INTEGER NOT NULL,
                expiresAt  INTEGER NOT NULL,

                FOREIGN KEY (userId)
                    REFERENCES users(id)
                    ON DELETE CASCADE
            );
        ');

        $pdo->exec('
            CREATE INDEX IF NOT EXISTS idx_sessions_userId
            ON sessions(userId);
        ');

        $pdo->exec('
            CREATE INDEX IF NOT EXISTS idx_sessions_expiresAt
            ON sessions(expiresAt);
        ');
    }
}
