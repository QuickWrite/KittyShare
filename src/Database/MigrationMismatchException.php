<?php

namespace KittyShare\Database;

use RuntimeException;

/**
 * Thrown when a migration file is newer than the expected schema version.
 */
final class MigrationMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $fileVersion,
        public readonly int $expectedVersion,
    ) {
        parent::__construct(
            "Migration file version V{$fileVersion} exceeds expected schema version {$expectedVersion}. " .
            'Bump DBVersionRepository::EXPECTED_VERSION in the same commit as the new migration file.',
        );
    }
}
