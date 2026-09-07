<?php

namespace KittyShare\Repository;

use KittyShare\Model\{User, Session, UserIdentity};

/**
 * Repository for managing authenticated user sessions.
 */
interface SessionRepository
{
    /**
     * Creates a new session for the given user identity.
     *
     * @param UserIdentity $user The user identity for which to create the session.
     * @return Session           The newly created session.
     */
    public function create(UserIdentity $user): Session;

    /**
     * Finds a session by its unique identifier.
     *
     * @param string $id    The unique identifier of the session.
     * @return Session|null The session if found, or null if no matching session exists.
     */
    public function find(string $id): ?Session;

    /**
     * Deletes the given session.
     *
     * @param Session $session The session to delete.
     * @return void
     */
    public function delete(Session $session): void;
}
