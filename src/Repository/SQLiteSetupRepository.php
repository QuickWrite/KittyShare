<?php

namespace KittyShare\Repository;

use Override;
use PDO;

use function assert;

final class SQLiteSetupRepository extends AbstractSQLiteRepository implements SetupRepository
{
    #[Override]
    public function setupRequired(): bool
    {
        $stmt = $this->pdo->query(
            'SELECT EXISTS (SELECT 1 FROM users);'
        );

        assert($stmt !== false, "Could not query database");

        /**
         * @var array<int> $result
         */
        $result = $stmt->fetch(PDO::FETCH_NUM);

        return $result[0] === 0;
    }
}
