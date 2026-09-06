<?php

namespace KittyShare;

use KittyShare\Repository\UserRepository;

class Dependencies
{
    public function __construct(
        private UserRepository $user_repository,
    ) {
    }

    public function getUserRepository(): UserRepository
    {
        return $this->user_repository;
    }
}
