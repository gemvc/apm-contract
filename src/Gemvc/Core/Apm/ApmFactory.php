<?php

namespace Gemvc\Core\Apm;

use Gemvc\Http\Request;

/**
 * APM Factory - Universal factory for APM provider instances
 * 
 * Universal factory that dynamically instantiates any APM provider based on
 * the APM_NAME environment variable. Follows Open/Closed Principle - new providers
 * can be added without modifying this factory.
 * 
 * Works like UniversalQueryExecuter for databases - provides a universal abstraction
 * layer that hides implementation details. The core framework uses ApmFactory::create()
 * without knowing which provider is installed.
 * 
 * Provider naming convention (standardized through init() process):
 * - APM_NAME="TraceKit" -> Gemvc\Core\Apm\Providers\TraceKit\TraceKitProvider
 * - APM_NAME="Datadog" -> Gemvc\Core\Apm\Providers\Datadog\DatadogProvider
 * - APM_NAME="NewRelic" -> Gemvc\Core\Apm\Providers\NewRelic\NewRelicProvider
 * 
 * Provider names are standardized by senior developers through the init() process.
 * Auto-discovers installed APM provider packages. No registration needed!
 * 
 * @package Gemvc\Core\Apm
 */
class ApmFactory
{
    /**
     * Create APM instance based on APM_NAME environment variable
     * 
     * Universal factory method that dynamically instantiates any APM provider
     * based on the APM_NAME environment variable. Follows Open/Closed Principle -
     * new providers can be added without modifying this factory.
     * 
     * Provider naming convention (standardized through init() process):
     * - APM_NAME="TraceKit" -> Gemvc\Core\Apm\Providers\TraceKit\TraceKitProvider
     * - APM_NAME="Datadog" -> Gemvc\Core\Apm\Providers\Datadog\DatadogProvider
     * 
     * Creates the provider instance which automatically loads configuration from
     * environment variables. The init() method is for setup/configuration via CLI/GUI,
     * not for runtime object creation.
     * 
     * @param Request|null $request The HTTP request object
     * @param array<string, mixed> $config Optional configuration override
     * @return ApmInterface|null APM instance or null if disabled/not configured/not found
     */
    public static function create(?Request $request = null, array $config = []): ?ApmInterface
    {
        $providerName = self::isEnabled();
        if ($providerName === null) {
            return null;
        }
        // Use provider name directly (init command sets standardized name)
        $className = "Gemvc\\Core\\Apm\\Providers\\{$providerName}\\{$providerName}Provider";
        // Check if provider package is installed
        if (!class_exists($className)) {
            if (self::isDevEnvironment()) {
                error_log("APM: Provider '{$providerName}' package not installed. Install with: composer require gemvc/apm-" . strtolower($providerName));
            }
            return null;
        }
        /** @var ApmInterface */
        return new $className($request, $config);
    }

    public static function isEnabled(): ?string
    {
        $apmName = $_ENV['APM_NAME'] ?? null;
        if (!is_string($apmName) || $apmName === '') {
            return null;
        }
        $enabled = $_ENV['APM_ENABLED'] ?? 'true';
        // Accept 'true', '1', or boolean true (consistent with AbstractApm)
        $isEnabled = is_string($enabled) ? ($enabled === 'true') : (bool)$enabled;
        if (!$isEnabled) {
            return null;
        }
        // Return as-is (init command sets it correctly)
        return $apmName;
    }
    /**
     * Check if running in development environment
     * 
     * @return bool
     */
    private static function isDevEnvironment(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'dev';
    }
}
