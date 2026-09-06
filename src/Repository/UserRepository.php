<?php

namespace KittyShare\Repository;

use KittyShare\Model\User;

/**
 * Provides access to the users for the application.
 */
interface UserRepository
{
    /**
     * Retrieves a user by their username.
     *
     * @param string $username The username of the user to retrieve.
     * @return User|null       The user if found, or null if not found.
     */
    public function getUser(string $username): ?User;


    /**
     * Creates a new user with the given username and password.
     *
     * @param string $username The username of the new user.
     * @param string $password The password of the new user.
     * @return User            The newly created user.
     */
    public function createUser(string $username, string $password): User;
}
