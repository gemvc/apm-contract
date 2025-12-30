![YoutubeHeader](https://github.com/user-attachments/assets/bcd88d92-7a64-4cfd-a3a8-7c70e2d60b09)

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
│  (Core Framework - uses ApmFactory to create APM)       │
└────────────────────┬────────────────────────────────────┘
                     │ requires
                     ▼
┌─────────────────────────────────────────────────────────┐
│              gemvc/apm-contracts                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ ApmInterface │  │ AbstractApm  │  │ ApmFactory   │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
└────────────────────┬────────────────────────────────────┘
                     │ implements/extends
                     │
        ┌────────────┼
        ▼            ▼          
┌─────────────┐ ┌──────────┐   
│ apm-tracekit│ │ other    │   
│  Provider   │ │ Provider │   
└─────────────┘ └──────────┘    
```

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
    "gemvc/apm-contracts": "^1.0",
    "gemvc/library": "^5.2"
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
        
        // Load common configuration using parent helpers
        $this->enabled = $this->parseEnabledFlag($config);
        $this->sampleRate = $this->parseSampleRate($config, 'YOURPROVIDER_SAMPLE_RATE', 1.0);
        $this->traceResponse = $this->parseBooleanFlag($config, 'trace_response', 'YOURPROVIDER_TRACE_RESPONSE', false);
        $this->traceDbQuery = $this->parseBooleanFlag($config, 'trace_db_query', 'YOURPROVIDER_TRACE_DB_QUERY', false);
        $this->traceRequestBody = $this->parseBooleanFlag($config, 'trace_request_body', 'YOURPROVIDER_TRACE_REQUEST_BODY', false);
        
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
            $this->rootSpan = $this->startTrace('http-request', $rootAttributes);
            
            if (empty($this->rootSpan)) {
                return;
            }
            
            // Register shutdown function to flush traces
            register_shutdown_function(function() {
                $this->flushOnShutdown();
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

### Step 4: Register in ApmFactory

Update `vendor/gemvc/apm-contracts/src/Gemvc/Core/Apm/ApmFactory.php` to add your provider:

```php
return match(strtolower(trim($apmName))) {
    'tracekit' => self::createTraceKit($request, $config),
    'yourprovider' => self::createYourProvider($request, $config), // Add this
    default => null
};
```

**Note:** In a real-world scenario, you would submit a PR to the `apm-contracts` repository to add your provider, or use a plugin/extension system.

### Step 5: Configuration

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
$this->tracekit = ApmFactory::create($this->request);
```

### 2. Factory Pattern

`ApmFactory` reads `APM_NAME` from environment and creates the appropriate provider:

```php
$apmName = $_ENV['APM_NAME'] ?? 'TraceKit';
return match($apmName) {
    'tracekit' => new TraceKitProvider($request),
    'datadog' => new DatadogProvider($request),
    // ...
};
```

### 3. Provider Initialization

Each provider extends `AbstractApm`, which:

1. **Stores Request object** - For accessing HTTP metadata
2. **Loads configuration** - Via `loadConfiguration()` (implemented by provider)
3. **Initializes root trace** - Via `initializeRootTrace()` (implemented by provider)
4. **Registers shutdown function** - To flush traces after HTTP response

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
$request->tracekit = $apmInstance;  // Backward compatibility
```

This allows all layers (Controller, UniversalQueryExecuter, Response, etc.) to access the same APM instance.

## 📚 API Reference

### ApmInterface

All APM providers must implement:

```php
interface ApmInterface
{
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
}
```

### AbstractApm

Provides shared functionality:

- **Request management** - `getRequest()`, `getRequestBodyForTracing()`
- **Configuration helpers** - `parseBooleanFlag()`, `parseSampleRate()`
- **Sampling** - `shouldSample()`
- **Utilities** - `limitStringForTracing()`, `determineStatusFromHttpCode()`

### ApmFactory

Static factory methods:

- `create(?Request $request, array $config = []): ?ApmInterface` - Create APM instance
- `isEnabled(): bool` - Check if APM is enabled and configured

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

## 🔗 Related Packages

- [gemvc/apm-tracekit](https://github.com/gemvc/apm-tracekit) - TraceKit APM provider implementation
- [gemvc/library](https://github.com/gemvc/library) - GEMVC core framework

## 📝 Environment Variables

### Core APM Variables

- `APM_NAME` - APM provider name (e.g., "TraceKit", "Datadog")
- `APM_ENABLED` - Enable/disable APM (`"true"` or `"false"`)
- `APM_API_KEY` - Unified API key (works for all providers)

### Provider-Specific Variables

Each provider may define additional variables (e.g., `TRACEKIT_API_KEY`, `DATADOG_API_KEY`).

## 🤝 Contributing

To add a new APM provider:

1. Create a new package following the structure above
2. Submit a PR to `apm-contracts` to register your provider in `ApmFactory`
3. Update documentation with your provider's configuration

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## Credits

Part of the [GEMVC PHP Framework built for Microservices ](https://gemvc.de) ecosystem.
