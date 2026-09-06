<?php

namespace KittyShare\Model;

class User
{
    public function __construct(
        private int $userId,
        private string $username,
        private string $passwordHash,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
}
