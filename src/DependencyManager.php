<?php

namespace KittyShare;

use KittyShare\Repository\{SQLiteUserRepository, SQLiteDatabase, SQLiteSessionRepository, SQLiteSetupRepository};

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
        $database = new SQLiteDatabase(__DIR__ . '/../database.sqlite');

        return new Dependencies(
            userRepository: new SQLiteUserRepository($database->getInstance()),
            setupRepository: new SQLiteSetupRepository($database->getInstance()),
            sessionRepository: new SQLiteSessionRepository($database->getInstance()),
        );
    }
}
