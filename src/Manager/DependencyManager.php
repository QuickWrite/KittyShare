<?php

namespace KittyShare\Manager;

use KittyShare\Model\Dependencies;
use KittyShare\Database\SQLiteDatabase;
use KittyShare\Repository\{
    SQLiteUserRepository,
    SQLiteSessionRepository,
    SQLiteSetupRepository,
    SQLiteShareRepository,
    SQLiteDBVersionRepository
};

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

        $pdo = SQLiteDatabase::connect($config->databasePath);

        // Ensure database has correct version
        (new SQLiteDBVersionRepository($pdo))->ensureValid($config->databasePath);

        $userRepository = new SQLiteUserRepository($pdo);
        $setupRepository = new SQLiteSetupRepository($pdo);
        $sessionRepository = new SQLiteSessionRepository($pdo, $config->sessionLifetime);
        $shareRepository = new SQLiteShareRepository($pdo);
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
