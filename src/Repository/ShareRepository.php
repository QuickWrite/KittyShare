<?php

namespace KittyShare\Repository;

use DateTimeInterface;
use KittyShare\Model\{UserIdentity, Share};

/**
 * Repository for managing file and folder shares.
 */
interface ShareRepository
{
    /**
     * Creates a new share for the given user and filepath.
     *
     * If the $expiresAt value is set to null, the share does not expire.
     *
     * @param UserIdentity       $user      The user creating the share.
     * @param string             $filepath  The path of the file or folder to share.
     * @param ?DateTimeInterface $expiresAt The date at which the share should expire (default = null)
     * @return Share                        The newly created share.
     */
    public function create(UserIdentity $user, string $filepath, ?DateTimeInterface $expiresAt = null): Share;

    /**
     * Finds a share by its unique identifier.
     *
     * @param string $id   The unique identifier of the share.
     * @return Share|null  The share if found, or null if no matching share exists.
     */
    public function find(string $id): ?Share;

    /**
     * Finds all shares created by the given user.
     *
     * @param UserIdentity $user The user whose shares should be returned.
     * @return list<Share>       The user's shares.
     */
    public function findByUser(UserIdentity $user): array;

    /**
     * Saves the given share.
     *
     * @param Share $share The share to save.
     * @return void
     */
    public function save(Share $share): void;

    /**
     * Deletes the given share.
     *
     * @param Share $share The share to delete.
     * @return void
     */
    public function delete(Share $share): void;
}
