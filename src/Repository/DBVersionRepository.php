<?php

namespace KittyShare\Repository;

/**
 * Provides read access to the database schema version.
 */
interface DBVersionRepository
{
    /**
     * Returns the schema version recorded in the database.
     *
     * @return int The current schema version.
     */
    public function currentVersion(): int;

    /**
     * Checks whether the database schema is up to date.
     *
     * @return bool True when the schema is up to date, false otherwise.
     */
    public function isValid(): bool;

    /**
     * Fails fast when the database schema is invalid.
     *
     * @param string $path Path to the database file (for the error message).
     *
     * @throws \KittyShare\Database\DatabaseVersionException If the version does
     * not match
     */
    public function ensureValid(string $path): void;
}
