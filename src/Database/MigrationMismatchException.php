<?php

namespace KittyShare\Database;

use RuntimeException;

/**
 * Thrown when the migration state does not line up with the expected
 * schema version: either a migration file is newer than the running
 * application understands, or the database itself is.
 */
final class MigrationMismatchException extends RuntimeException
{
    private function __construct(
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * Creates an exception for a migration file that is newer than the
     * running application understands.
     */
    public static function forMigrationFile(
        int $fileVersion,
        int $expectedVersion,
    ): self {
        return new self(
            "Migration file version V{$fileVersion} exceeds expected schema version {$expectedVersion}. " .
            'Bump the EXPECTED_VERSION in the same commit as the new migration file.',
        );
    }

    /**
     * Creates an exception for a database that is newer than the running
     * application understands. Migrations only move forward from older
     * versions, so there is nothing to apply.
     */
    public static function forNewerDatabase(
        int $currentVersion,
        int $expectedVersion,
    ): self {
        return new self(
            "Database schema version {$currentVersion} is newer than " .
            "expected schema version {$expectedVersion}. " .
            'The database was created by a newer application version.',
        );
    }
}
