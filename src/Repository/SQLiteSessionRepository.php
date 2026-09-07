<?php

namespace KittyShare\Repository;

use KittyShare\Model\{Session, UserIdentity};
use Override;
use PDO;
use DateTimeImmutable;

final class SQLiteSessionRepository extends AbstractSQLiteRepository implements SessionRepository
{
    #[Override]
    public function create(UserIdentity $user): Session
    {
        $sessionId = bin2hex(random_bytes(32));

        // Expires in 30 days
        $expiresAt = time() + 60 * 60 * 24 * 30;

        $stmt = $this->pdo->prepare('
            INSERT INTO sessions (id, userId, expiresAt)
            VALUES (:id, :userId, :expiresAt)
            RETURNING *;
        ');

        $stmt->execute([ 'id' => $sessionId, 'userId' => $user->userId, 'expiresAt' => $expiresAt ]);

        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);

        return new Session(
            id: $fetched['id'],
            user: $user,
            expiresAt: (new DateTimeImmutable())->setTimestamp((int) $fetched['expiresAt']),
        );
    }

    #[Override]
    public function find(string $id): ?Session
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                s.id AS sessionId,
                s.userId,
                s.expiresAt,
                u.username
            FROM sessions s
            INNER JOIN users u ON s.userId = u.id
            WHERE s.id = :id;
        ');

        $stmt->execute([ 'id' => $id ]);

        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetched === false) {
            return null;
        }

        return new Session(
            id: $fetched['sessionId'],
            user: new UserIdentity(
                userId: (int) $fetched['userId'],
                username: $fetched['username'],
            ),
            expiresAt: (new DateTimeImmutable())->setTimestamp((int) $fetched['expiresAt']),
        );
    }

    #[Override]
    public function delete(Session $session): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM sessions
            WHERE id = :id;
        ');

        $stmt->execute([ 'id' => $session->id ]);
    }
}
