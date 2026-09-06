<?php

namespace KittyShare\Repository;

use PDO;

/**
 * Base repository for SQLite-backed data access.
 *
 * Provides a shared PDO connection to concrete repository implementations.
 */
abstract class AbstractSQLiteRepository
{
    /**
     * @param PDO $pdo The PDO connection used for database operations.
     */
    public function __construct(
        protected PDO $pdo
    ) {
    }
}
