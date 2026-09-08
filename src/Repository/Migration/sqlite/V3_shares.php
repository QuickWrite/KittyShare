<?php

namespace KittyShare\Repository\Migration\sqlite;

use KittyShare\Repository\Migration\Migration;
use PDO;
use Override;

class MigrationV3 implements Migration
{
    #[Override]
    public static function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS shares (
                id         TEXT PRIMARY KEY,
                userId     INTEGER NOT NULL,
                filepath   TEXT NOT NULL,

                createdAt  INTEGER NOT NULL DEFAULT (unixepoch()),
                expiresAt  INTEGER,
                revokedAt  INTEGER,

                FOREIGN KEY (userId)
                    REFERENCES users(id)
                    ON DELETE CASCADE
            );
        ');

        $pdo->exec('
            CREATE INDEX IF NOT EXISTS idx_shares_userId
            ON shares(userId);
        ');
    }
}
