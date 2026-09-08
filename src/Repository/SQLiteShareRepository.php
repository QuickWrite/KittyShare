<?php

namespace KittyShare\Repository;

use DateTimeImmutable;
use KittyShare\Model\UserIdentity;
use KittyShare\Model\Share;
use DateTimeInterface;
use Override;
use PDO;

final class SQLiteShareRepository extends AbstractSQLiteRepository implements ShareRepository
{
    #[Override]
    public function create(UserIdentity $user, string $filepath, ?DateTimeInterface $expiresAt = null): Share
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO shares (userId, filepath, expiresAt)
            VALUES (:userId, :filepath, :expiresAt)
            RETURNING *;
        ');

        $stmt->execute([
            'userId' => $user->userId,
            'filepath' => $filepath,
            'expiresAt' => $expiresAt?->getTimestamp(),
        ]);

        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);

        return new Share(
            id: $fetched['id'],
            user: $user,
            filepath: $fetched['filepath'],
            createdAt: (new DateTimeImmutable())->setTimestamp((int) $fetched['createdAt']),
            expiresAt: $expiresAt,
            revokedAt: null,
        );
    }

    #[Override]
    public function find(string $id): ?Share
    {
        $stmt = $this->pdo->prepare('
            SELECT
                s.*,
                u.username
            FROM shares s
            INNER JOIN users u ON u.id = s.userId
            WHERE s.id = :id;
        ');

        $stmt->execute([
            'id' => $id,
        ]);

        $fetched = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fetched === false) {
            return null;
        }

        return $this->rowToShare($fetched);
    }

    #[Override]
    public function findByUser(UserIdentity $user): array
    {
        $stmt = $this->pdo->prepare('
            SELECT
                s.*,
                u.username
            FROM shares s
            INNER JOIN users u ON u.id = s.userId
            WHERE s.userId = :userId
            ORDER BY s.createdAt DESC;
        ');

        $stmt->execute([
            'userId' => $user->userId,
        ]);

        $shares = [];

        while ($fetched = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $shares[] = $this->rowToShare($fetched);
        }

        return $shares;
    }

    #[Override]
    public function save(Share $share): void
    {
        // Only mutable share properties are updated; the ID, owner and creation timestamp
        // are immutable and therefore must remain unchanged.
        $stmt = $this->pdo->prepare('
            UPDATE shares
            SET
                filepath = :filepath,
                expiresAt = :expiresAt,
                revokedAt = :revokedAt
            WHERE id = :id;
        ');

        $stmt->execute([
            'id' => $share->id,
            'filepath' => $share->filepath,
            'expiresAt' => $share->expiresAt?->getTimestamp(),
            'revokedAt' => $share->revokedAt?->getTimestamp(),
        ]);
    }

    #[Override]
    public function delete(Share $share): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM shares
            WHERE id = :id;
        ');

        $stmt->execute([
            'id' => $share->id,
        ]);
    }

    private function rowToShare(array $fetched): Share
    {
        return new Share(
            id: $fetched['id'],
            user: new UserIdentity(
                userId: (int) $fetched['userId'],
                username: $fetched['username'],
            ),
            filepath: $fetched['filepath'],
            createdAt: (new DateTimeImmutable())->setTimestamp((int) $fetched['createdAt']),
            expiresAt: $fetched['expiresAt'] !== null
                ? (new DateTimeImmutable())->setTimestamp((int) $fetched['expiresAt'])
                : null,
            revokedAt: $fetched['revokedAt'] !== null
                ? (new DateTimeImmutable())->setTimestamp((int) $fetched['revokedAt'])
                : null,
        );
    }
}
