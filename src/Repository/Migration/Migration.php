<?php

namespace KittyShare\Repository\Migration;

use PDO;

/**
 * Interface that all database migrations must implement.
 *
 * Each migration represents a versioned, repeatable schema change.
 *
 * Version numbers are extracted from the filename (V{number}_{name}.php)
 * and must be unique across all migration files.
 *
 * A migration MUST be idempotent: running it multiple times on the same
 * schema should never corrupt data or fail from "already exists" errors.
 */
interface Migration
{
    /**
     * Applies the schema changes for this migration version.
     *
     * The caller wraps this call in a transaction.
     * Implementations should NOT manage transactions themselves.
     *
     * @param PDO $pdo An open connection to the SQLite database.
     * @return void
     */
    public static function up(PDO $pdo): void;
}
