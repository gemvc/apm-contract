<?php

namespace Gemvc\Helper;

/**
 * PHPStan stub for ProjectHelper class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual ProjectHelper class exists in gemvc/library but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the methods used by gemvc/apm-contracts.
 */
class ProjectHelper
{
    /**
     * Check if the current environment is development
     * 
     * @return bool True if development environment, false otherwise
     */
    public static function isDevEnvironment(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'dev';
    }
}

