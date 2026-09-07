<?php

namespace KittyShare\Model;

use DateTimeImmutable;

final readonly class Session
{
    public function __construct(
        public string $id,
        public UserIdentity $user,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }
}
