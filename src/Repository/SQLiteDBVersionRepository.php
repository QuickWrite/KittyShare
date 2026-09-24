<?php

namespace KittyShare\Repository;

use KittyShare\Database\DatabaseVersionException;
use Override;

use function assert;

final class SQLiteDBVersionRepository extends AbstractSQLiteRepository implements DBVersionRepository
{
    public const EXPECTED_VERSION = 3;

    #[Override]
    public function currentVersion(): int
    {
        $versionQuery = $this->pdo->query('PRAGMA user_version');

        assert($versionQuery !== false, 'Could not execute query to check version.');

        return (int) $versionQuery->fetchColumn();
    }

    #[Override]
    public function isValid(): bool
    {
        return $this->currentVersion() == self::EXPECTED_VERSION;
    }

    #[Override]
    public function ensureValid(string $path): void
    {
        $current = $this->currentVersion();

        if (!$this->isValid()) {
            throw new DatabaseVersionException($current, self::EXPECTED_VERSION, $path);
        }
    }
}
