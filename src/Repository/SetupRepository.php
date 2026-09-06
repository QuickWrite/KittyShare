<?php

namespace KittyShare\Repository;

/**
 * Provides access to the application's setup state.
 */
interface SetupRepository
{
    /**
     * Determines whether the application setup is required.
     *
     * @return bool True if the setup is required, false otherwise.
     */
    public function setupRequired(): bool;
}
