<?php

namespace KittyShare;

use KittyShare\Repository\{SessionRepository, UserRepository, SetupRepository};

class Dependencies
{
    public function __construct(
        private UserRepository $user_repository,
        private SetupRepository $setup_repository,
        private SessionRepository $session_repository,
    ) {
    }

    public function getUserRepository(): UserRepository
    {
        return $this->user_repository;
    }

    public function getSetupRepository(): SetupRepository
    {
        return $this->setup_repository;
    }

    public function getSessionRepository(): SessionRepository
    {
        return $this->session_repository;
    }
}
