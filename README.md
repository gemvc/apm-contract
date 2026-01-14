![gemvc_header_for_github](https://github.com/user-attachments/assets/25268760-3a70-4077-a268-f0ed69eff938)

# GEMVC APM Contracts

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)](https://www.php.net/)

APM contracts, interfaces, and abstract base class for GEMVC framework. This package provides the foundation for all Application Performance Monitoring (APM) providers, enabling developers to create pluggable APM solutions that work seamlessly with GEMVC.

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Architecture](#architecture)
- [Creating an APM Provider Package](#creating-an-apm-provider-package)
- [How It Works](#how-it-works)
- [API Reference](#api-reference)
- [Examples](#examples)
- [License](#license)

##  Overview

The `gemvc/apm-contracts` package provides:

- **`ApmInterface`** - Contract that all APM providers must implement
- **`AbstractApm`** - Base class with shared functionality (request handling, utilities, configuration)
- **`ApmFactory`** - Factory for creating APM provider instances based on configuration
- **`ApmToolkitInterface`** - Contract that all APM provider toolkits must implement
- **`AbstractApmToolkit`** - Base class with shared functionality for client-side integration and management

This package is **always installed** with GEMVC (required by `gemvc/library`), providing a standardized way to integrate any APM solution (TraceKit, Datadog, New Relic, Elastic APM, OpenTelemetry, etc.) without changing application code.

## 📦 Installation

This package is automatically installed when you install GEMVC:

```bash
composer require gemvc/library
```

It's also required by APM provider packages:

```bash
composer require gemvc/apm-tracekit
# This automatically installs gemvc/apm-contracts
```

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    gemvc/library                        │
│  (Core Framework - uses ApmFactory::create() -         │
│   Universal abstraction, like UniversalQueryExecuter)   │
└────────────────────┬────────────────────────────────────┘
                     │ requires
                     ▼
┌─────────────────────────────────────────────────────────┐
│              gemvc/apm-contracts                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ ApmInterface │  │ AbstractApm  │  │ ApmFactory   │   │
│  │  (Contract)  │  │  (Base)      │  │ (Universal) │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ApmToolkitInterface│ │AbstractApmToolkit│              │
│  │   (Contract)     │  │    (Base)        │              │
│  └──────────────────┘  └──────────────────┘              │
└────────────────────┬────────────────────────────────────┘
                     │ implements/extends
                     │ (Auto-discovered via dynamic instantiation)
        ┌────────────┼────────────┐
        ▼            ▼            ▼          
┌─────────────┐ ┌──────────┐ ┌──────────┐   
│ apm-tracekit│ │ Datadog  │ │ NewRelic│   
│  Provider   │ │ Provider │ │ Provider │   
│  + Toolkit  │ │ + Toolkit│ │ + Toolkit│   
└─────────────┘ └──────────┘ └──────────┘    
     (Any provider - no factory modification needed!)
```

**Universal Pattern:** Just like `UniversalQueryExecuter` abstracts database connections (PDO/OpenSwoole/MongoDB), `ApmFactory` abstracts APM providers (TraceKit/Datadog/NewRelic). The core framework uses `ApmFactory::create()` without knowing which provider is installed.

## Creating an APM Provider Package

Follow these steps to create your own APM provider package for GEMVC:

### Step 1: Create Package Structure

```bash
mkdir gemvc-apm-yourprovider
cd gemvc-apm-yourprovider
composer init
```

### Step 2: Set Up composer.json

```json
{
  "name": "gemvc/apm-yourprovider",
  "description": "YourProvider APM provider for GEMVC framework",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": ">=8.2",
    "gemvc/apm-contracts": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "Gemvc\\Core\\Apm\\Providers\\YourProvider\\": "src/Gemvc/Core/Apm/Providers/YourProvider/"
    }
  }
}
```

### Step 3: Create Provider Class

Create `src/Gemvc/Core/Apm/Providers/YourProvider/YourProvider.php`:

```php
<?php
namespace Gemvc\Core\Apm\Providers\YourProvider;

use Gemvc\Core\Apm\AbstractApm;
use Gemvc\Helper\ProjectHelper;

/**
 * YourProvider APM Implementation
 */
class YourProvider extends AbstractApm
{
    // YourProvider-specific configuration
    private string $apiKey;
    private string $endpoint;
    
    /**
     * Load YourProvider-specific configuration
     */
    protected function loadConfiguration(array $config = []): void
    {
        // Load API key (check both YOURPROVIDER_API_KEY and APM_API_KEY)
        $this->apiKey = $config['api_key'] 
            ?? $_ENV['YOURPROVIDER_API_KEY'] 
            ?? $_ENV['APM_API_KEY'] 
            ?? '';
        
        // Load endpoint
        $this->endpoint = $config['endpoint'] 
            ?? $_ENV['YOURPROVIDER_ENDPOINT'] 
            ?? 'https://api.yourprovider.com/v1/traces';
        
        // Note: Common properties (enabled, sampleRate, traceResponse, etc.) are already
        // set in the constructor from $config or $_ENV. You can override them here if needed:
        // $this->enabled = $this->parseBooleanFlag($config, 'enabled', 'APM_ENABLED', true);
        // $this->sampleRate = $this->parseSampleRate($config, 'YOURPROVIDER_SAMPLE_RATE', 1.0);
        // $this->traceResponse = $this->parseBooleanFlag($config, 'trace_response', 'YOURPROVIDER_TRACE_RESPONSE', false);
        // $this->traceDbQuery = $this->parseBooleanFlag($config, 'trace_db_query', 'YOURPROVIDER_TRACE_DB_QUERY', false);
        // $this->traceRequestBody = $this->parseBooleanFlag($config, 'trace_request_body', 'YOURPROVIDER_TRACE_REQUEST_BODY', false);
        
        // Disable if no API key
        if (empty($this->apiKey)) {
            $this->enabled = false;
        }
    }
    
    /**
     * Initialize root trace from Request object
     */
    protected function initializeRootTrace(): void
    {
        if ($this->request === null) {
            return;
        }
        
        try {
            // Build root span attributes from Request
            $rootAttributes = [
                'http.method' => $this->request->getMethod(),
                'http.url' => $this->request->getUri(),
                'http.user_agent' => $this->request->getHeader('User-Agent') ?? 'unknown',
                'http.route' => $this->request->getServiceName() . '/' . $this->request->getMethodName(),
            ];
            
            // Optionally include request body if enabled
            if ($this->shouldTraceRequestBody()) {
                $requestBody = $this->getRequestBodyForTracing();
                if ($requestBody !== null) {
                    $rootAttributes['http.request.body'] = self::limitStringForTracing($requestBody);
                }
            }
            
            // Start root trace (implement your provider's trace creation)
            $this->rootSpan = $this->startSpan('http-request', $rootAttributes);
            
            if (empty($this->rootSpan)) {
                return;
            }
            
            // Register shutdown function to flush traces
            register_shutdown_function(function() {
                $this->flush();
            });
        } catch (\Throwable $e) {
            // Silently fail - don't let APM break the application
            if (ProjectHelper::isDevEnvironment()) {
                error_log("YourProvider: Failed to initialize root trace: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Start a child span (implement your provider's span creation)
     */
    public function startSpan(string $operationName, array $attributes = [], int $kind = self::SPAN_KIND_INTERNAL): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        
        // Implement your provider's span creation logic
        // Return: ['span_id' => string, 'trace_id' => string, 'start_time' => int]
    }
    
    /**
     * End a span (implement your provider's span ending logic)
     */
    public function endSpan(array $spanData, array $finalAttributes = [], ?string $status = self::STATUS_OK): void
    {
        // Implement your provider's span ending logic
    }
    
    /**
     * Record an exception on a span
     */
    public function recordException(array $spanData, \Throwable $exception): array
    {
        // Implement your provider's exception recording logic
    }
    
    /**
     * Flush traces (send to your provider's service)
     */
    public function flush(): void
    {
        // Implement your provider's trace sending logic
        // Use AsyncApiCall for non-blocking sending
    }
    
    // Implement other required methods from ApmInterface...
}
```

### Step 4: Create Toolkit Class

All APM providers must have a Toolkit class for client-side integration and management. Create `src/Gemvc/Core/Apm/Providers/YourProvider/YourProviderToolkit.php`:

```php
<?php
namespace Gemvc\Core\Apm\Providers\YourProvider;

use Gemvc\Core\Apm\AbstractApmToolkit;

/**
 * YourProvider Toolkit - Client-Side Integration & Management
 */
class YourProviderToolkit extends AbstractApmToolkit
{
    /**
     * Get provider-specific API key environment variable name
     */
    protected function getProviderApiKeyEnvName(): ?string
    {
        return 'YOURPROVIDER_API_KEY';
    }
    
    /**
     * Get provider-specific base URL environment variable name
     */
    protected function getProviderBaseUrlEnvName(): ?string
    {
        return 'YOURPROVIDER_BASE_URL';
    }
    
    /**
     * Get provider-specific service name environment variable name
     */
    protected function getProviderServiceNameEnvName(): ?string
    {
        return 'YOURPROVIDER_SERVICE_NAME';
    }
    
    /**
     * Get default base URL
     */
    protected function getDefaultBaseUrl(): string
    {
        return 'https://api.yourprovider.com';
    }
    
    /**
     * Get registration endpoint
     */
    protected function getRegisterEndpoint(): string
    {
        return '/v1/integrate/register';
    }
    
    /**
     * Get verification endpoint
     */
    protected function getVerifyEndpoint(): string
    {
        return '/v1/integrate/verify';
    }
    
    /**
     * Get status endpoint
     */
    protected function getStatusEndpoint(): string
    {
        return '/v1/integrate/status';
    }
    
    /**
     * Get heartbeat endpoint
     */
    protected function getHeartbeatEndpoint(): string
    {
        return '/v1/health/heartbeat';
    }
    
    /**
     * Get health checks endpoint
     */
    protected function getHealthChecksEndpoint(): string
    {
        return '/api/health-checks';
    }
    
    /**
     * Get metrics endpoint (use {serviceName} placeholder)
     */
    protected function getMetricsEndpoint(): string
    {
        return '/api/metrics/services/{serviceName}';
    }
    
    /**
     * Get alerts summary endpoint
     */
    protected function getAlertsSummaryEndpoint(): string
    {
        return '/v1/alerts/summary';
    }
    
    /**
     * Get active alerts endpoint
     */
    protected function getActiveAlertsEndpoint(): string
    {
        return '/v1/alerts/active';
    }
    
    /**
     * Get webhooks endpoint
     */
    protected function getWebhooksEndpoint(): string
    {
        return '/v1/webhooks';
    }
    
    /**
     * Get subscription endpoint
     */
    protected function getSubscriptionEndpoint(): string
    {
        return '/v1/billing/subscription';
    }
    
    /**
     * Get plans endpoint
     */
    protected function getPlansEndpoint(): string
    {
        return '/v1/billing/plans';
    }
    
    /**
     * Get checkout session endpoint
     */
    protected function getCheckoutSessionEndpoint(): string
    {
        return '/v1/billing/create-checkout-session';
    }
}
```

**Toolkit Features:**
- Account registration and email verification
- Health check monitoring (synchronous and asynchronous)
- Service metrics and alerts
- Webhook management
- Subscription and billing information

The abstract base class provides all helper methods for API calls, error handling, and JSON parsing. You only need to implement the endpoint paths.

### Step 5: No Registration Required!

**The factory automatically discovers your provider!** No need to modify `ApmFactory.php`.

The factory uses dynamic class instantiation based on the `APM_NAME` environment variable:
- `APM_NAME="YourProvider"` → Automatically looks for `Gemvc\Core\Apm\Providers\YourProvider\YourProviderProvider`
- If the class exists, it's instantiated automatically
- If the class doesn't exist, the factory gracefully returns `null`

This follows the **Open/Closed Principle** - you can add new providers without modifying the factory!

### Step 6: Configuration

Users configure your provider in `.env`:

```env
APM_NAME="YourProvider"
APM_ENABLED="true"
YOURPROVIDER_API_KEY="your-api-key"
# or use unified key:
APM_API_KEY="your-api-key"
```

## 🔧 How It Works

### 1. Framework Initialization

When GEMVC starts processing a request:

```php
// In ApiService constructor
$this->apm = ApmFactory::create($this->request);
```

### 2. Factory Pattern

`ApmFactory` reads `APM_NAME` from environment and creates the appropriate provider:

```php
$apmName = $_ENV['APM_NAME'];
// Factory dynamically instantiates provider based on APM_NAME
// Format: Gemvc\Core\Apm\Providers\{ProviderName}\{ProviderName}Provider
```

### 3. Provider Initialization

Each provider extends `AbstractApm`, which:

1. **Stores Request object** - For accessing HTTP metadata
2. **Sets common properties** - From `$config` array (if provided) or `$_ENV` variables:
   - `$apmName` - From `$config['apm_name']` or `$_ENV['APM_NAME']`
   - `$enabled` - From `$config['enabled']` or `$_ENV['APM_ENABLED']` (accepts `'true'`, `'1'`, `'false'`, `'0'`, or boolean)
   - `$sampleRate` - From `$config['sample_rate']` or `$_ENV['APM_SAMPLE_RATE']` (clamped to 0.0-1.0)
   - `$traceResponse`, `$traceDbQuery`, `$traceRequestBody` - From config or environment
3. **Loads provider-specific configuration** - Via `loadConfiguration()` (implemented by provider, can override common properties)
4. **Initializes root trace** - Via `initializeRootTrace()` (implemented by provider)
5. **Registers shutdown function** - To flush traces after HTTP response

### 4. Span Management

Throughout the request lifecycle, the framework calls:

- `startSpan()` - Create child spans (database queries, controller operations, etc.)
- `endSpan()` - End spans with final attributes and status
- `recordException()` - Record exceptions on spans
- `flush()` - Send traces to APM service (non-blocking)

### 5. Request Object Integration

The APM instance is stored in `Request` object for sharing:

```php
$request->apm = $apmInstance;  // Primary property
```

This allows all layers (Controller, UniversalQueryExecuter, Response, etc.) to access the same APM instance.

### 6. Toolkit Usage

APM toolkits are instantiated separately and provide client-side integration features:

```php
use Gemvc\Core\Apm\Providers\TraceKit\TraceKitToolkit;

// Create toolkit instance
$toolkit = new TraceKitToolkit();

// Register new service
$response = $toolkit->registerService('user@example.com');
if ($response->response_code === 200) {
    $sessionId = $response->data['session_id'];
    // Send verification code to user
}

// Verify code and get API key
$response = $toolkit->verifyCode($sessionId, $code);
if ($response->response_code === 200) {
    $apiKey = $response->data['api_key'];
    // Save API key to .env or configuration
}

// Send health heartbeat (asynchronous, non-blocking)
$toolkit->sendHeartbeatAsync('healthy', [
    'memory_usage' => memory_get_usage(true),
    'cpu_usage' => sys_getloadavg()[0] ?? 0,
]);

// Get service metrics
$metrics = $toolkit->getMetrics('15m');

// Manage webhooks
$toolkit->createWebhook('alerts', 'https://example.com/webhook', ['alert.created']);
```

## 📚 API Reference

### ApmInterface

All APM providers must implement:

```php
interface ApmInterface
{
    public function init(array $config = []): bool;
    public function isEnabled(): bool;
    public function startSpan(string $operationName, array $attributes = [], int $kind = self::SPAN_KIND_INTERNAL): array;
    public function endSpan(array $spanData, array $finalAttributes = [], ?string $status = self::STATUS_OK): void;
    public function recordException(array $spanData, \Throwable $exception): array;
    public function shouldTraceResponse(): bool;
    public function shouldTraceDbQuery(): bool;
    public function shouldTraceRequestBody(): bool;
    public static function determineStatusFromHttpCode(int $statusCode): string;
    public static function limitStringForTracing(string $value): string;
    public function getTraceId(): ?string;
    public function flush(): void;
    
    // Constants - OpenTelemetry standard
    public const SPAN_KIND_UNSPECIFIED = 0;
    public const SPAN_KIND_INTERNAL = 1;
    public const SPAN_KIND_SERVER = 2;
    public const SPAN_KIND_CLIENT = 3;
    public const SPAN_KIND_PRODUCER = 4;
    public const SPAN_KIND_CONSUMER = 5;
    
    public const STATUS_OK = 'OK';
    public const STATUS_ERROR = 'ERROR';
}
```

### AbstractApm

Provides shared functionality:

- **Initialization** - `init()` method for setup/configuration via CLI/GUI tools
- **Request management** - `getRequest()`, `getRequestBodyForTracing()`
- **Configuration helpers** - `parseBooleanFlag()`, `parseSampleRate()`
- **Sampling** - `shouldSample()`
- **Utilities** - `limitStringForTracing()`, `determineStatusFromHttpCode()`

**Note:** The `init()` method is designed for setup/configuration processes (CLI commands or GUI tools), not for runtime object creation. The constructor automatically loads configuration from environment variables during normal operation.

### ApmFactory

Universal factory methods (no provider-specific code):

- `create(?Request $request, array $config = []): ?ApmInterface` - Dynamically create APM instance based on `APM_NAME`. The `$config` array can override environment variables for runtime configuration.
- `isEnabled(): ?string` - Lightweight check if APM is enabled. Returns APM provider name if enabled, `null` otherwise. Accepts `APM_ENABLED` values: `"true"`, `"1"`, or boolean `true`. Performance optimized - avoids double-checking `APM_NAME`.

**Provider Naming Convention:**
- Provider names are standardized through the `init()` process by senior developers
- Provider name from `APM_NAME` must be in correct PascalCase format (e.g., "TraceKit", "Datadog")
- Class name format: `Gemvc\Core\Apm\Providers\{ProviderName}\{ProviderName}Provider`
- Example: `APM_NAME="TraceKit"` → `Gemvc\Core\Apm\Providers\TraceKit\TraceKitProvider`

### ApmToolkitInterface

Contract for all APM provider toolkits. Toolkits handle client-side integration and management:

**Configuration Methods:**
- `setApiKey(string $apiKey): self` - Set API key
- `setServiceName(string $serviceName): self` - Set service name

**Account Management:**
- `registerService(string $email, ?string $organizationName, string $source, array $sourceMetadata): JsonResponse` - Register new service
- `verifyCode(string $sessionId, string $code): JsonResponse` - Verify email and get API key
- `getStatus(): JsonResponse` - Check integration status

**Health Monitoring:**
- `sendHeartbeat(string $status, array $metadata): JsonResponse` - Send synchronous heartbeat
- `sendHeartbeatAsync(string $status, array $metadata): void` - Send asynchronous heartbeat (non-blocking)
- `listHealthChecks(): JsonResponse` - List health checks

**Metrics & Alerts:**
- `getMetrics(string $window): JsonResponse` - Get service metrics
- `getAlertsSummary(): JsonResponse` - Get alerts overview
- `getActiveAlerts(int $limit): JsonResponse` - Get active alerts

**Webhooks:**
- `createWebhook(string $name, string $url, array $events, bool $enabled): JsonResponse` - Create webhook
- `listWebhooks(): JsonResponse` - List webhooks

**Billing:**
- `getSubscription(): JsonResponse` - Get subscription info
- `listPlans(): JsonResponse` - List available plans
- `createCheckoutSession(string $planId, string $billingInterval, string $source, ?string $successUrl, ?string $cancelUrl): JsonResponse` - Create checkout session

### AbstractApmToolkit

Base class providing shared functionality for all toolkits:

- **Helper Methods** - `createApiCall()`, `parseJsonResponse()`, `makeGetRequest()`, `makePostRequest()`
- **Error Handling** - Consistent error responses via `JsonResponse`
- **API Key Management** - Automatic loading from environment variables
- **Async Support** - Built-in support for non-blocking heartbeats

Provider toolkits extend this class and implement abstract methods for provider-specific endpoint paths.

## 💡 Examples

### Example: Simple APM Provider

```php
class SimpleProvider extends AbstractApm
{
    protected function loadConfiguration(array $config = []): void
    {
        $this->enabled = !empty($_ENV['SIMPLE_API_KEY']);
    }
    
    protected function initializeRootTrace(): void
    {
        // Simple implementation
        $this->rootSpan = ['trace_id' => uniqid(), 'span_id' => uniqid()];
    }
    
    public function startSpan(string $operationName, array $attributes = [], int $kind = self::SPAN_KIND_INTERNAL): array
    {
        return ['span_id' => uniqid(), 'trace_id' => $this->getTraceId(), 'start_time' => time()];
    }
    
    public function endSpan(array $spanData, array $finalAttributes = [], ?string $status = self::STATUS_OK): void
    {
        // Log span data
        error_log("Span ended: " . json_encode($spanData));
    }
    
    public function recordException(array $spanData, \Throwable $exception): array
    {
        error_log("Exception: " . $exception->getMessage());
        return $spanData;
    }
    
    public function flush(): void
    {
        // Send traces
    }
}
```

### Example: Using APM in Custom Code

```php
// Get APM instance from Request
$apm = $request->apm;

if ($apm !== null && $apm->isEnabled()) {
    // Start a custom span
    $span = $apm->startSpan('custom-operation', [
        'custom.attribute' => 'value'
    ]);
    
    try {
        // Your code here
        $result = doSomething();
        
        // End span with success
        $apm->endSpan($span, ['result' => 'success'], ApmInterface::STATUS_OK);
    } catch (\Throwable $e) {
        // Record exception
        $apm->recordException($span, $e);
        $apm->endSpan($span, ['result' => 'error'], ApmInterface::STATUS_ERROR);
        throw $e;
    }
}
```

### Example: Using APM Toolkit

```php
use Gemvc\Core\Apm\Providers\TraceKit\TraceKitToolkit;

// Initialize toolkit
$toolkit = new TraceKitToolkit();

// Or with explicit API key and service name
$toolkit = new TraceKitToolkit('your-api-key', 'my-service');

// Register new service (first-time setup)
$response = $toolkit->registerService('admin@example.com', 'My Organization');
if ($response->response_code === 200) {
    $sessionId = $response->data['session_id'];
    // User receives verification code via email
}

// Verify code and activate service
$response = $toolkit->verifyCode($sessionId, '123456');
if ($response->response_code === 200) {
    $apiKey = $response->data['api_key'];
    // Save to .env: TRACEKIT_API_KEY=$apiKey
}

// Send periodic health heartbeat (non-blocking)
$toolkit->sendHeartbeatAsync('healthy', [
    'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
    'cpu_load' => sys_getloadavg()[0] ?? 0,
]);

// Get service metrics
$metrics = $toolkit->getMetrics('1h');

// Create webhook for alerts
$toolkit->createWebhook(
    'production-alerts',
    'https://example.com/webhooks/alerts',
    ['alert.created', 'alert.resolved'],
    true
);

// Check subscription status
$subscription = $toolkit->getSubscription();
```

## 🔗 Related Packages

- [gemvc/apm-tracekit](https://github.com/gemvc/apm-tracekit) - TraceKit APM provider implementation
- [gemvc/library](https://github.com/gemvc/library) - GEMVC core framework

## 📝 Environment Variables

### Core APM Variables

- `APM_NAME` - APM provider name (e.g., "TraceKit", "Datadog")
- `APM_ENABLED` - Enable/disable APM (accepts `"true"`, `"1"`, `"false"`, `"0"`, or boolean; defaults to `"true"` if not set)
- `APM_SAMPLE_RATE` - Sample rate for traces (0.0 to 1.0, where 1.0 = 100%; defaults to 1.0)
- `APM_TRACE_RESPONSE` - Enable/disable response tracing (accepts `"true"`, `"1"`, `"false"`, `"0"`, or boolean; defaults to `false`)
- `APM_TRACE_DB_QUERY` - Enable/disable database query tracing (accepts `"true"`, `"1"`, `"false"`, `"0"`, or boolean; defaults to `false`)
- `APM_TRACE_REQUEST_BODY` - Enable/disable request body tracing (accepts `"true"`, `"1"`, `"false"`, `"0"`, or boolean; defaults to `false`)
- `APM_SEND_INTERVAL` - Batch send interval in seconds (default: 5). Controls how often batched traces are sent to APM provider. Lower values = more frequent sends with smaller batches. Minimum: 1 second.
- `APM_API_KEY` - Unified API key (works for all providers)
- `APM_MAX_STRING_LENGTH` - Maximum string length for tracing (default: 2000). Used by `limitStringForTracing()` to truncate long strings

### Provider-Specific Variables

Each provider may define additional variables (e.g., `TRACEKIT_API_KEY`, `DATADOG_API_KEY`).

## 🛠️ Development Setup

This package uses PHPStan stubs for development to avoid circular dependencies with `gemvc/library`:

- **Stub Files:** Located in `stubs/` directory
  - `stubs/Gemvc/Http/Request.php` - Stub for Request class
  - `stubs/Gemvc/Helper/ProjectHelper.php` - Stub for ProjectHelper class
- **PHPStan Configuration:** Stubs are configured in `phpstan.neon`
- **Autoload:** Stubs are autoloaded in `composer.json` under `autoload-dev`
- **Testing:** `MockRequest` class extends stub `Request` for unit testing

**Note:** `gemvc/library` is not in the `require` section to prevent circular dependencies during development. The package will work correctly when installed as a dependency of `gemvc/library` or APM provider packages.

## 🤝 Contributing

To add a new APM provider:

1. Create a new package following the structure above
2. Submit a PR to `apm-contracts` to register your provider in `ApmFactory`
3. Update documentation with your provider's configuration

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## Credits

Part of the [GEMVC PHP Framework built for Microservices ](https://gemvc.de) ecosystem.
