<?php

namespace KittyShare\Model;

final readonly class User extends UserIdentity
{
    public function __construct(
        int $userId,
        string $username,
        public string $passwordHash,
    ) {
        parent::__construct($userId, $username);
    }
}
