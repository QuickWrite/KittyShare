<?php

namespace KittyShare;

use KittyShare\Repository\{SessionRepository, UserRepository, SetupRepository};

final readonly class Dependencies
{
    public function __construct(
        public UserRepository $userRepository,
        public SetupRepository $setupRepository,
        public SessionRepository $sessionRepository,
    ) {
    }
}
