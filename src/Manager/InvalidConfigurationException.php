<?php

namespace KittyShare\Manager;

use RuntimeException;

/**
 * Thrown when an application setting is configured but invalid.
 */
final class InvalidConfigurationException extends RuntimeException
{
    public function __construct(
        public readonly string $variable,
        public readonly ?string $value,
        string $expected,
    ) {
        parent::__construct(
            sprintf(
                "Invalid configuration for %s: %s. Expected %s.",
                $variable,
                $value === null ? 'null' : "'" . $value . "'",
                $expected,
            ),
        );
    }
}
