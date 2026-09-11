<?php

namespace KittyShare\Repository;

use Override;
use PDO;
use KittyShare\Model\User;

use function password_hash;
use function is_array;

final class SQLiteUserRepository extends AbstractSQLiteRepository implements UserRepository
{
    #[Override]
    public function getUser(string $username): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM users u
            WHERE u.username = :username;
        ');

        $stmt->execute(['username' => $username]);

        /**
         * @var false|array{'id': int|string, 'username': string, 'passwordHash': string} $result
         */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($result)) {
            return null;
        }

        return self::dbToUser($result);
    }

    #[Override]
    public function createUser(string $username, string $password): User
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare('
            INSERT INTO users (username, passwordHash)
            VALUES (:username, :passwordHash)
            RETURNING *;
        ');

        $stmt->execute(['username' => $username, 'passwordHash' => $passwordHash]);

        /**
         * @var array{'id': int|string, 'username': string, 'passwordHash': string} $result
         */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return self::dbToUser($result);
    }

    /**
     * Converts a database row into a User object
     * 
     * @param array{'id': int|string, 'username': string, 'passwordHash': string} $fetched The fetched row
     * @return User The converted user object
     */
    private static function dbToUser(array $fetched): User
    {
        return new User(
            userId: (int) $fetched['id'],
            username: $fetched['username'],
            passwordHash: $fetched['passwordHash'],
        );
    }
}
