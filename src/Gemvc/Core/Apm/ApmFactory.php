<?php
namespace Gemvc\Core\Apm;

use Gemvc\Http\Request;

/**
 * APM Factory - Creates APM provider instances based on configuration
 * 
 * Uses APM_NAME environment variable to determine which provider to instantiate.
 * Auto-discovers installed APM provider packages.
 * 
 * @example
 * APM_NAME="TraceKit" -> TraceKitProvider
 * APM_NAME="Datadog" -> DatadogProvider
 * APM_NAME="NewRelic" -> NewRelicProvider
 * 
 * @package Gemvc\Core\Apm
 */
class ApmFactory
{
    /**
     * Create APM instance based on APM_NAME environment variable
     * 
     * @param Request|null $request The HTTP request object
     * @param array<string, mixed> $config Optional configuration override
     * @return ApmInterface|null APM instance or null if disabled/not configured
     */
    public static function create(?Request $request = null, array $config = []): ?ApmInterface
    {
        $apmName = $_ENV['APM_NAME'] ?? 'TraceKit';
        $enabled = ($_ENV['APM_ENABLED'] ?? 'true') === 'true';
        
        if (!$enabled) {
            return null;
        }
        
        $apmNameString = is_string($apmName) ? $apmName : 'TraceKit';
        return match(strtolower(trim($apmNameString))) {
            'tracekit' => self::createTraceKit($request, $config),
            // Future providers (uncomment when packages are created):
            // 'datadog' => self::createDatadog($request, $config),
            // 'newrelic' => self::createNewRelic($request, $config),
            // 'elasticapm' => self::createElasticApm($request, $config),
            // 'opentelemetry' => self::createOpenTelemetry($request, $config),
            default => null
        };
    }
    
    /**
     * Check if APM is enabled and configured
     * 
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $apmName = $_ENV['APM_NAME'] ?? 'TraceKit';
        $enabled = ($_ENV['APM_ENABLED'] ?? 'true') === 'true';
        
        if (!$enabled) {
            return false;
        }
        
        $apmNameString = is_string($apmName) ? $apmName : 'TraceKit';
        // Check provider-specific configuration
        return match(strtolower(trim($apmNameString))) {
            'tracekit' => self::isTraceKitConfigured(),
            // Future providers:
            // 'datadog' => self::isDatadogConfigured(),
            // 'newrelic' => self::isNewRelicConfigured(),
            // 'elasticapm' => self::isElasticApmConfigured(),
            default => false
        };
    }
    
    /**
     * Create TraceKit provider instance
     * 
     * @param Request|null $request
     * @param array<string, mixed> $config
     * @return ApmInterface|null
     */
    private static function createTraceKit(?Request $request, array $config): ?ApmInterface
    {
        // Check if TraceKit provider package is installed
        if (!class_exists('Gemvc\\Core\\Apm\\Providers\\TraceKit\\TraceKitProvider')) {
            if (self::isDevEnvironment()) {
                error_log("APM: TraceKit provider package not installed. Install with: composer require gemvc/apm-tracekit");
            }
            return null;
        }
        
        // @phpstan-ignore-next-line - TraceKitProvider implements ApmInterface, but class may not exist at analysis time
        return new \Gemvc\Core\Apm\Providers\TraceKit\TraceKitProvider($request, $config);
    }
    
    /**
     * Check if TraceKit is configured
     * 
     * Checks both TRACEKIT_API_KEY and APM_API_KEY (for unified API key support)
     * 
     * @return bool
     */
    private static function isTraceKitConfigured(): bool
    {
        return !empty($_ENV['TRACEKIT_API_KEY']) || !empty($_ENV['APM_API_KEY']);
    }
    
    /**
     * Get ProjectHelper class (to avoid direct dependency)
     * 
     * @return bool
     */
    private static function isDevEnvironment(): bool
    {
        return ($_ENV['APP_ENV'] ?? '') === 'dev';
    }
    
    // Future provider factory methods (uncomment when packages are created):
    /*
    private static function createDatadog(?Request $request, array $config): ?ApmInterface
    {
        if (!class_exists('Gemvc\\Core\\Apm\\Providers\\Datadog\\DatadogProvider')) {
            return null;
        }
        return new \Gemvc\Core\Apm\Providers\Datadog\DatadogProvider($request, $config);
    }
    
    private static function isDatadogConfigured(): bool
    {
        return !empty($_ENV['DATADOG_API_KEY']);
    }
    */
}

