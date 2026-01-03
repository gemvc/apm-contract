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
        // Use isEnabled() to get APM name and check if enabled (performance optimized)
        $providerName = self::isEnabled();
        if ($providerName === null) {
            return null;
        }
        
        $className = self::buildProviderClassName($providerName);
        
        // Check if provider package is installed
        if (!class_exists($className)) {
            if (self::isDevEnvironment()) {
                error_log("APM: Provider '{$providerName}' package not installed. Install with: composer require gemvc/apm-{$providerName}");
            }
            return null;
        }
        
        // Dynamically instantiate the provider
        try {
            /** @var ApmInterface */
            return new $className($request, $config);
        } catch (\Throwable $e) {
            if (self::isDevEnvironment()) {
                error_log("APM: Failed to create provider '{$providerName}': " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Check if APM is enabled and has very basic configuration
     * 
     * Returns the APM provider name if enabled, null otherwise.
     * This is a lightweight check that only verifies APM_NAME is set
     * and APM_ENABLED is 'true'. Full provider configuration is handled
     * via the init() command during setup.
     * 
     * Performance optimized - avoids double-checking APM_NAME.
     * 
     * @return string|null APM name if enabled and configured, null otherwise
     */
    public static function isEnabled(): ?string
    {
        $apmName = $_ENV['APM_NAME'] ?? null;
        if (!is_string($apmName) || $apmName === '') {
            return null;
        }
        $enabled = $_ENV['APM_ENABLED'] ?? 'true';
        // Accept 'true', '1', or boolean true (consistent with AbstractApm)
        $isEnabled = is_string($enabled) ? ($enabled === 'true' || $enabled === '1') : (bool)$enabled;
        if (!$isEnabled) {
            return null;
        }
        return $apmName;
    }
    
    /**
     * Build provider class name from provider name
     * 
     * Provider names are standardized through the init() process, so no normalization is needed.
     * 
     * @param string $providerName Provider name from APM_NAME (e.g., "TraceKit")
     * @return string Fully qualified class name
     */
    private static function buildProviderClassName(string $providerName): string
    {
        $normalized = self::normalizeProviderName($providerName);
        return "Gemvc\\Core\\Apm\\Providers\\{$normalized}\\{$normalized}Provider";
    }

    /**
     * Normalize provider name to match class name format
     * 
     * Converts case variations to the correct class name format.
     * Handles special cases like tracekit -> TraceKit.
     * 
     * @param string $providerName Provider name from APM_NAME (e.g., "tracekit", "Tracekit", "TRACEKIT")
     * @return string Normalized provider name (e.g., "TraceKit")
     */
    public static function normalizeProviderName(string $providerName): string
    {
        // Special case: tracekit -> TraceKit (any case variation)
        if (strtolower($providerName) === 'tracekit') {
            return 'TraceKit';
        }
        
        // Default: capitalize first letter (e.g., datadog -> Datadog, newrelic -> Newrelic)
        return ucfirst($providerName);
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

