<?php

namespace KittyShare;

use KittyShare\Repository\{SessionRepository, UserRepository, SetupRepository, ShareRepository};

final readonly class Dependencies
{
    public function __construct(
        public UserRepository $userRepository,
        public SetupRepository $setupRepository,
        public SessionRepository $sessionRepository,
        public ShareRepository $shareRepository,
    ) {
    }
}
