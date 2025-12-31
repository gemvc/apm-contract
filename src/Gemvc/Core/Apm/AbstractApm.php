<?php
namespace Gemvc\Core\Apm;

use Gemvc\Http\Request;
use Gemvc\Helper\ProjectHelper;

/**
 * Abstract APM Base Class - Shared implementation for all APM providers
 * 
 * This abstract class provides common functionality that all APM providers share:
 * - Request object management
 * - Common utility methods
 * - Request body extraction
 * - Configuration patterns
 * 
 * Provider-specific implementations must extend this class and implement
 * abstract methods for span creation, trace sending, etc.
 * 
 * @package Gemvc\Core\Apm
 */
abstract class AbstractApm implements ApmInterface
{
    /**
     * Request object for full request lifecycle tracking
     * 
     * @var Request|null
     */
    protected ?Request $request = null;
    
    /**
     * APM provider name from environment variable
     * 
     * @var string|null
     */
    protected ?string $apmName = null;
    
    /**
     * Whether APM is enabled
     * 
     * @var bool
     */
    protected bool $enabled = false;
    
    /**
     * Sample rate (0.0 to 1.0, where 1.0 = 100%)
     * 
     * @var float
     */
    protected float $sampleRate = 1.0;
    
    /**
     * Whether to trace response data
     * 
     * @var bool
     */
    protected bool $traceResponse = false;
    
    /**
     * Whether to trace database queries
     * 
     * @var bool
     */
    protected bool $traceDbQuery = false;
    
    /**
     * Whether to trace request body
     * 
     * @var bool
     */
    protected bool $traceRequestBody = false;
    
    /**
     * Current trace ID
     * 
     * @var string|null
     */
    protected ?string $traceId = null;
    
    /**
     * Root span for HTTP request (managed internally)
     * 
     * @var array<string, mixed>
     */
    protected array $rootSpan = [];
    
    /**
     * Cached value of mt_getrandmax() for performance
     * 
     * @var float|null
     */
    private static ?float $cachedRandMax = null;
    
    /**
     * Constructor - Initializes APM provider from environment variables
     * 
     * At runtime, instances are created with configuration loaded from environment variables.
     * The init() method is available for setup/configuration via CLI/GUI tools.
     * 
     * @param Request|null $request The HTTP request object
     * @param array<string, mixed> $config Optional configuration override
     */
    public function __construct(?Request $request = null, array $config = [])
    {
        $this->request = $request;
        
        // Set properties from config array (if provided) or environment variables
        // Config array takes precedence for runtime overrides
        $apmNameValue = $config['apm_name'] ?? $_ENV['APM_NAME'] ?? null;
        $this->apmName = is_string($apmNameValue) ? $apmNameValue : null;
        
        // Parse enabled flag (handle string 'true'/'false' or boolean)
        $enabledValue = $config['enabled'] ?? $_ENV['APM_ENABLED'] ?? 'true';
        $this->enabled = is_string($enabledValue) ? ($enabledValue === 'true' || $enabledValue === '1') : (bool)$enabledValue;
        
        // Parse sample rate (convert to float and clamp between 0.0 and 1.0)
        $sampleRateValue = $config['sample_rate'] ?? $_ENV['APM_SAMPLE_RATE'] ?? 1.0;
        $this->sampleRate = is_numeric($sampleRateValue) ? max(0.0, min(1.0, (float)$sampleRateValue)) : 1.0;
        
        // Parse boolean flags (handle string 'true'/'false' or boolean)
        $traceResponseValue = $config['trace_response'] ?? $_ENV['APM_TRACE_RESPONSE'] ?? false;
        $this->traceResponse = is_string($traceResponseValue) ? ($traceResponseValue === 'true' || $traceResponseValue === '1') : (bool)$traceResponseValue;
        
        $traceDbQueryValue = $config['trace_db_query'] ?? $_ENV['APM_TRACE_DB_QUERY'] ?? false;
        $this->traceDbQuery = is_string($traceDbQueryValue) ? ($traceDbQueryValue === 'true' || $traceDbQueryValue === '1') : (bool)$traceDbQueryValue;
        
        $traceRequestBodyValue = $config['trace_request_body'] ?? $_ENV['APM_TRACE_REQUEST_BODY'] ?? false;
        $this->traceRequestBody = is_string($traceRequestBodyValue) ? ($traceRequestBodyValue === 'true' || $traceRequestBodyValue === '1') : (bool)$traceRequestBodyValue;
        
        // Load provider-specific configuration from environment variables
        $this->loadConfiguration($config);
        
        // Store instance in Request object for sharing
        if ($this->request !== null) {
            $this->request->apm = $this;
        }
        
        // Automatically initialize root trace if Request is provided and tracing is enabled
        if ($this->request !== null && $this->isEnabled()) {
            $this->initializeRootTrace();
        }
    }
    
    /**
     * Initialize APM provider with configuration (for setup/configuration via CLI/GUI)
     * 
     * This method is called during setup/configuration process (via CLI command or GUI)
     * to configure the provider. It loads provider-specific environment variables and
     * can be used to set up the .env file or configuration.
     * 
     * This is NOT called during runtime object creation - the constructor handles that.
     * 
     * @param array<string, mixed> $config Optional configuration override
     * @return bool True if initialization was successful, false otherwise
     */
    public function init(array $config = []): bool
    {
        try {
            // Load provider-specific configuration
            $this->loadConfiguration($config);
            
            // Store instance in Request object for sharing (if Request is available)
            if ($this->request !== null) {
                $this->request->apm = $this;
            }
            
            // Automatically initialize root trace if Request is provided and tracing is enabled
            if ($this->request !== null && $this->isEnabled()) {
                $this->initializeRootTrace();
            }
            
            return true;
        } catch (\Throwable $e) {
            // Log error in dev environment
            if (ProjectHelper::isDevEnvironment()) {
                error_log("APM: Initialization failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Load provider-specific configuration
     * 
     * Each provider must implement this to load its own configuration
     * from environment variables or config array.
     * 
     * @param array<string, mixed> $config Optional configuration override
     * @return void
     */
    abstract protected function loadConfiguration(array $config = []): void;
    
    /**
     * Initialize root trace from Request object
     * 
     * Each provider must implement this to start the root trace
     * with provider-specific span creation.
     * 
     * @return void
     */
    abstract protected function initializeRootTrace(): void;
    
    /**
     * Check if APM is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    
    /**
     * Check if response tracing is enabled
     * 
     * @return bool
     */
    public function shouldTraceResponse(): bool
    {
        return $this->traceResponse;
    }
    
    /**
     * Check if database query tracing is enabled
     * 
     * @return bool
     */
    public function shouldTraceDbQuery(): bool
    {
        return $this->traceDbQuery;
    }
    
    /**
     * Check if request body tracing is enabled
     * 
     * @return bool
     */
    public function shouldTraceRequestBody(): bool
    {
        return $this->traceRequestBody;
    }
    
    /**
     * Get current trace ID
     * 
     * @return string|null
     */
    public function getTraceId(): ?string
    {
        return $this->traceId;
    }
    
    /**
     * Get request body for tracing (reconstructs from parsed data)
     * 
     * Since php://input can only be read once and is already consumed,
     * we reconstruct the body from the parsed request data.
     * 
     * Always tries to format as JSON for better readability in traces,
     * falls back to URL-encoded format only if JSON encoding fails.
     * 
     * @return string|null The request body as string, or null if no body data
     */
    protected function getRequestBodyForTracing(): ?string
    {
        if ($this->request === null) {
            return null;
        }
        
        try {
            $method = $this->request->getMethod();
            
            // Get body data based on method
            $bodyData = null;
            if ($method === 'POST' && !empty($this->request->post)) {
                $bodyData = $this->request->post;
            } elseif ($method === 'PUT' && !empty($this->request->put)) {
                $bodyData = $this->request->put;
            } elseif ($method === 'PATCH' && !empty($this->request->patch)) {
                $bodyData = $this->request->patch;
            }
            
            if ($bodyData === null) {
                return null;
            }
            
            // Always try to format as JSON first (more readable in traces)
            $json = json_encode($bodyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if ($json !== false) {
                return $json;
            }
            
            // Fallback to URL-encoded format if JSON encoding fails
            return http_build_query($bodyData);
        } catch (\Throwable $e) {
            // Silently fail - don't let request body tracing break the application
            if (ProjectHelper::isDevEnvironment()) {
                error_log("APM: Failed to get request body for tracing: " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Check if request should be sampled
     * 
     * @param bool $forceSample Force sampling (e.g., for errors) - always returns true if enabled
     * @return bool
     */
    protected function shouldSample(bool $forceSample = false): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        
        // Always sample errors regardless of sample rate
        if ($forceSample) {
            return true;
        }
        
        if ($this->sampleRate >= 1.0) {
            return true;
        }
        
        if ($this->sampleRate <= 0.0) {
            return false;
        }
        
        // Cache mt_getrandmax() result - it's a constant that never changes
        if (self::$cachedRandMax === null) {
            self::$cachedRandMax = (float)mt_getrandmax();
        }
        
        return (mt_rand() / self::$cachedRandMax) < $this->sampleRate;
    }
    
    /**
     * Parse boolean flag from config array or environment variable(s)
     * Handles both string ('true', '1', 'false', '0') and boolean values
     * 
     * @param array<string, mixed> $config Configuration array
     * @param string $configKey Config array key name
     * @param string $envKey Primary environment variable key
     * @param bool $default Default value if not found
     * @param string|null $envKey2 Optional secondary environment variable key (checked after primary)
     * @return bool Parsed boolean value
     */
    protected function parseBooleanFlag(array $config, string $configKey, string $envKey, bool $default = false, ?string $envKey2 = null): bool
    {
        $value = $config[$configKey] 
            ?? $_ENV[$envKey] 
            ?? ($envKey2 !== null ? ($_ENV[$envKey2] ?? null) : null)
            ?? $default;
        
        if (is_string($value)) {
            return $value === 'true' || $value === '1';
        }
        
        return (bool)$value;
    }
    
    /**
     * Parse sample rate from config array or environment variable
     * Clamps value between 0.0 and 1.0
     * 
     * @param array<string, mixed> $config Configuration array
     * @param string $envKey Environment variable key
     * @param float $default Default value
     * @return float Sample rate between 0.0 and 1.0
     */
    protected function parseSampleRate(array $config, string $envKey, float $default = 1.0): float
    {
        $sampleRate = $config['sample_rate'] ?? $_ENV[$envKey] ?? $default;
        
        if (!is_numeric($sampleRate)) {
            return $default;
        }
        
        $rate = (float)$sampleRate;
        return max(0.0, min(1.0, $rate));
    }
    
    /**
     * Determine span status from HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @return string STATUS_ERROR if >= 400, otherwise STATUS_OK
     */
    public static function determineStatusFromHttpCode(int $statusCode): string
    {
        return $statusCode >= 400 ? self::STATUS_ERROR : self::STATUS_OK;
    }
    
    /**
     * Limit string size for tracing (to avoid huge traces)
     * 
     * Truncates strings longer than the configured max length.
     * Max length is configurable via APM_MAX_STRING_LENGTH env var (default: 2000).
     * 
     * @param string $value The string to limit
     * @return string Limited string (max length from config)
     */
    public static function limitStringForTracing(string $value): string
    {
        // Get max length from environment variable (default: 2000)
        $maxLength = self::getMaxStringLength();
        
        if (strlen($value) > $maxLength) {
            // Truncate to maxLength - 3 to make room for '...'
            return substr($value, 0, $maxLength - 3) . '...';
        }
        return $value;
    }
    
    /**
     * Get maximum string length for tracing from environment variable
     * 
     * Reads APM_MAX_STRING_LENGTH from environment.
     * Returns default 2000 if not set or invalid.
     * 
     * @return int Maximum string length (default 2000)
     */
    private static function getMaxStringLength(): int
    {
        $envValue = $_ENV['APM_MAX_STRING_LENGTH'] ??  null;
        
        if ($envValue === null) {
            return 2000; // Default value
        }
        
        // Parse as integer, use default if not numeric
        return is_numeric($envValue) ? (int)$envValue : 2000;
    }
    
    /**
     * Get the Request object
     * 
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }
}

