<?php

namespace KittyShare;

use KittyShare\Manager\AuthenticationManager;
use KittyShare\Repository\{SQLiteUserRepository, SQLiteDatabase, SQLiteSessionRepository, SQLiteSetupRepository, SQLiteShareRepository};

class DependencyManager
{
    private static ?Dependencies $dependencies = null;

    public static function get(): Dependencies
    {
        if (self::$dependencies == null) {
            self::$dependencies = self::constructDependencies();
        }

        return self::$dependencies;
    }

    private static function constructDependencies(): Dependencies
    {
        $config = ConfigManager::get();

        $database = new SQLiteDatabase($config->databasePath);

        $userRepository = new SQLiteUserRepository($database->getInstance());
        $setupRepository = new SQLiteSetupRepository($database->getInstance());
        $sessionRepository = new SQLiteSessionRepository($database->getInstance(), $config->sessionLifetime);
        $shareRepository = new SQLiteShareRepository($database->getInstance());
        $authenticationManager = new AuthenticationManager($userRepository, $sessionRepository);

        return new Dependencies(
            userRepository: $userRepository,
            setupRepository: $setupRepository,
            sessionRepository: $sessionRepository,
            shareRepository: $shareRepository,
            authenticationManager: $authenticationManager,
        );
    }
}
