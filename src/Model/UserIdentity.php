<?php

namespace KittyShare\Model;

readonly class UserIdentity
{
    public function __construct(
        public int $userId,
        public string $username,
    ) {
    }
}
