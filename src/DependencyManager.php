<?php

namespace KittyShare;

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
        return new Dependencies(
        );
    }
}
