<?php

namespace KittyShare\Database;

use PDO;

/**
 * Opens the connection to the given SQLite database
 */
final class SQLiteDatabase
{
    private function __construct()
    {
    }

    /**
     * Opens a connection with general settings
     * 
     * @param string $path Path to the SQLite database file.
     */
    public static function connect(string $path): PDO
    {
        $instance = new PDO("sqlite:$path");
        $instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $instance->exec('PRAGMA journal_mode=WAL');
        $instance->exec('PRAGMA foreign_keys = ON');
        $instance->exec('PRAGMA busy_timeout = 3000');

        return $instance;
    }
}
