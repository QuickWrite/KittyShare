<?php

namespace KittyShare\Model;

use KittyShare\Model\UserIdentity;
use DateTimeImmutable;

final readonly class Share
{
    public function __construct(
        public string             $id,
        public UserIdentity       $user,
        public string             $filepath,
        public DateTimeImmutable  $createdAt,
        public ?DateTimeImmutable $expiresAt = null,
        public ?DateTimeImmutable $revokedAt = null,
    ) {
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null
            && $this->expiresAt->getTimestamp() <= time();
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    public function revoke(): self
    {
        return new self(
            id: $this->id,
            user: $this->user,
            filepath: $this->filepath,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            revokedAt: (new DateTimeImmutable())->setTimestamp((int) time()),
        );
    }

    public function unrevoke(): self
    {
        return new self(
            id: $this->id,
            user: $this->user,
            filepath: $this->filepath,
            createdAt: $this->createdAt,
            expiresAt: $this->expiresAt,
            revokedAt: null,
        );
    }
}
