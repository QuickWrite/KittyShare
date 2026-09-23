<?php

namespace KittyShare\Database;

use RuntimeException;

/**
 * Thrown when the version of the database is different than what the application expected.
 */
final class DatabaseVersionException extends RuntimeException
{
    public function __construct(
        public readonly int $currentVersion,
        public readonly int $expectedVersion,
        public readonly string $databasePath,
    ) {
        if ($currentVersion < $expectedVersion) {
            parent::__construct(
                "Database schema version {$currentVersion} is older than expected version {$expectedVersion}. " .
                    'Run the migration first: php bin/migrate (or composer migrate).',
            );
        } else {
            parent::__construct(
                "Database schema version {$currentVersion} is somehow younger than the expected version {$expectedVersion}.\n" .
                    'Please open an issue at https://github.com/QuickWrite/KittyShare/issues with this exception and your settings.'
            );
        }
    }
}
