<?php

namespace KittyShare\Repository;

use Override;
use PDO;

final class SQLiteSetupRepository extends AbstractSQLiteRepository implements SetupRepository
{
    #[Override]
    public function setupRequired(): bool
    {
        $result = $this->pdo->query(
            'SELECT EXISTS (SELECT 1 FROM users);'
        )->fetch(PDO::FETCH_NUM);

        return $result[0] == 0;
    }
}
