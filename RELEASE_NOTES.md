# Release Notes

## Version 1.3.2 - Performance Improvement & Error Handling

**Release Date:** 2026-01-03

### Overview

This release improves performance and error handling in `ApmFactory` by adding a class existence check before attempting to instantiate APM provider classes. This prevents unnecessary exception handling and improves efficiency when providers are not installed.

### Performance Improvements

#### ApmFactory Class Existence Check

- **Performance Optimization** - Added `class_exists()` check before instantiating provider classes
- **Reduced Exception Overhead** - Eliminates try-catch block for non-existent classes, improving performance
- **Better Error Handling** - Returns `null` gracefully when provider package is not installed instead of throwing exceptions
- **Dev Environment Logging** - Only logs helpful error messages in development environment when provider is missing

**Before:**
```php
// Attempted instantiation could throw exception
try {
    return new $className($request, $config);
} catch (\Throwable $e) {
    // Exception handling overhead
    if (self::isDevEnvironment()) {
        error_log("APM: Failed to create provider...");
    }
    return null;
}
```

**After:**
```php
// Fast class existence check before instantiation
if (!class_exists($className)) {
    if (self::isDevEnvironment()) {
        error_log("APM: Provider '{$providerName}' package not installed...");
    }
    return null;
}
return new $className($request, $config);
```

### Changes

#### ApmFactory

- Added `class_exists()` check in `create()` method before provider instantiation (line 58)
- Removed try-catch block for cleaner, more efficient error handling
- Improved performance by checking class existence before attempting instantiation
- Maintains dev environment logging for better developer experience

### Migration Guide

**No breaking changes** - This release is fully backward compatible.

**For Users:**
- No action required
- Improved performance when APM providers are not installed
- Better error messages in development environment

**For Provider Developers:**
- No changes required
- Provider packages continue to work as before

### Changelog

**Performance:**
- Added `class_exists()` check before provider instantiation in `ApmFactory::create()`
- Removed try-catch block for non-existent classes, reducing exception handling overhead
- Improved performance when provider packages are not installed

**Error Handling:**
- Better error handling for missing provider packages
- Dev environment logging only when provider is not installed

---

## Version 1.3.1 - Provider Name Normalization Bugfix

**Release Date:** 2026-01-03

### Overview

This release fixes a critical bug where APM providers were not being detected in production due to case-sensitivity issues with the `APM_NAME` environment variable. Provider names are now normalized to ensure consistent class name resolution regardless of case variations.

### Bug Fixes

#### Provider Name Normalization

- **Fixed APM Detection Issue** - Provider names are now normalized to PascalCase before building class names
- **Case-Insensitive Matching** - Any case variation of the provider name (e.g., "Tracekit", "tracekit", "TRACEKIT", "TraceKit") will now correctly resolve to the same provider class
- **Production Fix** - Resolves issue where APM was not being detected in production environments due to case mismatches in `APM_NAME` environment variable

**Before:**
```php
// Different cases = different classes (caused detection failures)
APM_NAME="Tracekit"  -> Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider
APM_NAME="tracekit"  -> Gemvc\Core\Apm\Providers\tracekit\tracekitProvider (not found)
APM_NAME="TraceKit"  -> Gemvc\Core\Apm\Providers\TraceKit\TraceKitProvider (not found)
```

**After:**
```php
// All cases normalize to same class
APM_NAME="Tracekit"  -> Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider ✅
APM_NAME="tracekit"  -> Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider ✅
APM_NAME="TraceKit"  -> Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider ✅
APM_NAME="TRACEKIT"  -> Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider ✅
```

### Changes

#### ApmFactory

- Added provider name normalization in `buildProviderClassName()` method (line 114)
- Provider names are normalized using `ucfirst(strtolower($providerName))` to ensure consistent PascalCase format
- This ensures that any case variation in `APM_NAME` environment variable will correctly resolve to the provider class

### Migration Guide

**No breaking changes** - This release is fully backward compatible.

**For Users:**
- No action required
- Your existing `APM_NAME` environment variable will now work regardless of case (e.g., "Tracekit", "tracekit", "TRACEKIT" all work)
- Recommended: Use PascalCase format (e.g., "Tracekit") for consistency, but any case will work

**For Provider Developers:**
- Ensure your provider class follows PascalCase naming convention
- Example: `Gemvc\Core\Apm\Providers\Tracekit\TracekitProvider` (not `tracekitProvider` or `TRACEKITProvider`)

### Changelog

**Fixed:**
- Provider name case-sensitivity issue causing APM detection failures in production
- Added normalization to `ApmFactory::buildProviderClassName()` to convert any case variation to PascalCase
- All case variations of `APM_NAME` now correctly resolve to the same provider class

---

## Version 1.3.0 - Test Coverage Improvements & Toolkit Testing

**Release Date:** 2025-12-31

### Overview

This release significantly improves test coverage for the APM Toolkit functionality, bringing overall coverage to 81.32% lines and 75.00% methods. All Toolkit interface methods are now comprehensively tested.

### What's New

#### Test Coverage Improvements

- **AbstractApmToolkit Coverage** - Improved from 24.32% to 75.68% lines (112/148 lines)
- **Overall Coverage** - Improved from 51.75% to 81.32% lines (209/257 lines)
- **Method Coverage** - Improved from 52.27% to 75.00% methods (33/44 methods)
- **New Tests** - Added 25 new tests covering all Toolkit interface methods

#### Comprehensive Toolkit Testing

- **Account Management** - Tests for `registerService()`, `verifyCode()`, `getStatus()`
- **Health Monitoring** - Tests for `sendHeartbeat()`, `sendHeartbeatAsync()`, `listHealthChecks()`
- **Metrics & Alerts** - Tests for `getMetrics()`, `getAlertsSummary()`, `getActiveAlerts()`
- **Webhooks** - Tests for `createWebhook()`, `listWebhooks()`
- **Billing** - Tests for `getSubscription()`, `listPlans()`, `createCheckoutSession()`
- **Helper Methods** - Tests for `makeGetRequest()`, `makePostRequest()` with various scenarios

### Changes

#### Testing

- Added 25 comprehensive tests for `AbstractApmToolkit` public interface methods
- Added tests for error handling scenarios (missing API key, invalid responses)
- Added tests for edge cases (empty API key, default parameters, various configurations)
- All tests pass with 101 total tests, 179 assertions, 6 skipped

### Test Statistics

- **Total Tests:** 101 (was 76)
- **Assertions:** 179 (was 150)
- **Skipped:** 6 (integration tests requiring Request class)
- **Line Coverage:** 81.32% (209/257 lines) - improved from 51.75%
- **Method Coverage:** 75.00% (33/44 methods) - improved from 52.27%

### Per-Class Coverage

- **AbstractApm:** 93.02% lines (80/86), 86.67% methods (13/15) ✅
- **AbstractApmToolkit:** 75.68% lines (112/148), 68.00% methods (17/25) ✅
- **ApmFactory:** 73.91% lines (17/23), 75.00% methods (3/4) ✅

### Migration Guide

**No breaking changes** - This release is fully backward compatible.

No migration required. All existing code continues to work as before.

### Changelog

**Added:**
- 25 new tests for `AbstractApmToolkit` interface methods
- Tests for all Toolkit account management methods
- Tests for all Toolkit health monitoring methods
- Tests for all Toolkit metrics and alerts methods
- Tests for all Toolkit webhook methods
- Tests for all Toolkit billing methods
- Tests for helper methods (`makeGetRequest`, `makePostRequest`) with error scenarios

**Testing:**
- Test coverage improved from 51.75% to 81.32% lines
- Test coverage improved from 52.27% to 75.00% methods
- AbstractApmToolkit coverage improved from 24.32% to 75.68% lines
- All new tests passing

---

## Version 1.2.0 - APM Toolkit Support & Performance Improvements

**Release Date:** 2025-12-31

### Overview

This release introduces APM Toolkit support for client-side integration and management, along with performance optimizations and enhanced configuration flexibility. All APM providers now have a standardized Toolkit interface for account management, health monitoring, metrics, alerts, webhooks, and billing.

### What's New

#### Performance Improvements

- **Reduced Overhead** - Common properties (`$apmName`, `$enabled`, `$sampleRate`, trace flags) are now set once at construction time instead of being checked repeatedly
- **Direct Property Assignment** - Properties are set directly from `$config` array or `$_ENV` variables in the constructor, before `loadConfiguration()` is called
- **Simplified Logic** - Removed unnecessary repeated environment variable checks

#### Configuration Enhancements

- **Config Array Support** - Constructor now accepts `$config` array that takes precedence over environment variables
- **Improved Boolean Parsing** - Enhanced parsing logic that accepts multiple formats:
  - String values: `'true'`, `'1'`, `'false'`, `'0'`
  - Boolean values: `true`, `false`
- **Consistent Parsing** - `ApmFactory::isEnabled()` now uses the same parsing logic as `AbstractApm` constructor for consistency

#### New Property

- **`$apmName` Property** - Added `protected ?string $apmName` property to `AbstractApm` to store the APM provider name once, avoiding repeated environment variable checks

#### APM Toolkit Support

- **`ApmToolkitInterface`** - New interface contract for all APM provider toolkits
- **`AbstractApmToolkit`** - Base class with shared functionality for client-side integration
- **Account Management** - Registration, email verification, and status checking
- **Health Monitoring** - Synchronous and asynchronous heartbeat support
- **Metrics & Alerts** - Service metrics, alerts summary, and active alerts
- **Webhook Management** - Create and list webhooks for event notifications
- **Billing Integration** - Subscription info, plan listing, and checkout session creation
- **Helper Methods** - Shared API call helpers, JSON parsing, and error handling

### Changes

#### New Classes

- **`ApmToolkitInterface`** - Interface contract defining all methods toolkits must implement
- **`AbstractApmToolkit`** - Abstract base class with shared functionality:
  - API key and service name management
  - HTTP request helpers (GET, POST with error handling)
  - JSON response parsing
  - Async heartbeat support (non-blocking)
  - Provider-specific endpoint configuration via abstract methods

#### AbstractApm

- Added `protected ?string $apmName = null;` property
- Constructor now sets common properties directly from `$config` or `$_ENV` before `loadConfiguration()`:
  - `$apmName` from `$config['apm_name']` or `$_ENV['APM_NAME']`
  - `$enabled` from `$config['enabled']` or `$_ENV['APM_ENABLED']` (with improved parsing)
  - `$sampleRate` from `$config['sample_rate']` or `$_ENV['APM_SAMPLE_RATE']` (with validation and clamping)
  - `$traceResponse`, `$traceDbQuery`, `$traceRequestBody` from config or environment (with improved parsing)
- Properties can still be overridden in `loadConfiguration()` if needed

#### ApmFactory

- `isEnabled()` now accepts `'true'`, `'1'`, or boolean `true` for `APM_ENABLED` (consistent with `AbstractApm`)
- Improved consistency between factory and abstract class parsing logic

#### Stub Files

- Added stubs for `Gemvc\Http\ApiCall`, `Gemvc\Http\AsyncApiCall`, `Gemvc\Http\JsonResponse`, `Gemvc\Http\Response`
- Updated PHPStan configuration to include new stubs
- Stubs enable development without circular dependencies

### Documentation Updates

- Updated README.md with:
  - Toolkit interface and abstract class documentation
  - Toolkit usage examples
  - Provider creation guide including Toolkit implementation
  - Architecture diagram updated to show Toolkit layer
  - API reference for ApmToolkitInterface and AbstractApmToolkit
- Updated provider examples to reflect new initialization pattern
- Added Toolkit examples for account management, health monitoring, and billing

### Testing

- Added tests for config array support in constructor
- Added tests for environment variable fallback
- Added tests for `'1'` and `'0'` boolean parsing in `ApmFactory`
- Added comprehensive test coverage for all uncovered methods:
  - `getRequestBodyForTracing()` - 7 tests (POST/PUT/PATCH scenarios)
  - `shouldSample()` - 5 tests (sampling logic)
  - `parseBooleanFlag()` - 4 tests (config/env parsing)
  - `parseSampleRate()` - 5 tests (rate parsing and clamping)
  - `getMaxStringLength()` - 3 tests (via `limitStringForTracing`)
  - `getTraceId()` and `getRequest()` - 2 tests (simple getters)
- All existing tests pass
- **76 tests total, 150 assertions, 6 skipped** (pending gemvc/library updates)
- **Test coverage: 51.75% lines, 52.27% methods** (before Toolkit tests)

### Migration Guide

**No breaking changes** - This release is fully backward compatible.

#### For Provider Developers

If your provider's `loadConfiguration()` method was setting common properties, you can now:

1. **Option 1:** Remove the property assignments from `loadConfiguration()` - they're already set in the constructor
2. **Option 2:** Keep them if you need provider-specific logic or overrides

Example:

```php
// Before (still works, but redundant)
protected function loadConfiguration(array $config = []): void
{
    $this->enabled = $this->parseBooleanFlag($config, 'enabled', 'APM_ENABLED', true);
    $this->sampleRate = $this->parseSampleRate($config, 'SAMPLE_RATE', 1.0);
    // ... provider-specific config
}

// After (simplified - common properties already set)
protected function loadConfiguration(array $config = []): void
{
    // Common properties already set in constructor
    // Only set provider-specific properties here
    $this->apiKey = $config['api_key'] ?? $_ENV['PROVIDER_API_KEY'] ?? '';
}
```

#### For Users

No changes required. The new config array support provides additional flexibility:

```php
// Runtime config override (new capability)
$apm = ApmFactory::create($request, [
    'enabled' => true,
    'sample_rate' => 0.5,
    'trace_response' => true,
]);
```

#### For Provider Developers - Toolkit Implementation

All APM providers must now implement a Toolkit class:

```php
// Create YourProviderToolkit extending AbstractApmToolkit
class YourProviderToolkit extends AbstractApmToolkit
{
    protected function getProviderApiKeyEnvName(): ?string
    {
        return 'YOURPROVIDER_API_KEY';
    }
    
    protected function getDefaultBaseUrl(): string
    {
        return 'https://api.yourprovider.com';
    }
    
    // Implement all abstract endpoint methods...
}
```

The abstract base class provides all helper methods - you only need to implement endpoint paths.

### Breaking Changes

- **Removed `$tracekit` backward compatibility property** - Only `$request->apm` is now used (gemvc/library 5.2.2+)
- **Removed `gemvc/library` from require** - Package now uses PHPStan stubs for development to avoid circular dependencies

### Bug Fixes

- Fixed operator precedence issue in boolean parsing
- Fixed inconsistency between `ApmFactory::isEnabled()` and `AbstractApm` constructor parsing

### Changelog

#### 1.1.0 (2025-01-XX)

**Added:**
- `ApmToolkitInterface` - Contract for all APM provider toolkits
- `AbstractApmToolkit` - Base class with shared toolkit functionality
- Toolkit support for account management, health monitoring, metrics, alerts, webhooks, and billing
- Stub files for `ApiCall`, `AsyncApiCall`, `JsonResponse`, `Response` classes
- `$apmName` property to `AbstractApm` class
- Config array support in constructor (takes precedence over `$_ENV`)
- Enhanced boolean parsing (accepts `'true'`, `'1'`, `'false'`, `'0'`, or boolean)
- Consistent parsing logic between `ApmFactory` and `AbstractApm`

**Changed:**
- Constructor now sets common properties directly from `$config` or `$_ENV` before `loadConfiguration()`
- `ApmFactory::isEnabled()` now accepts `'1'` as `true` (consistent with `AbstractApm`)
- Improved performance by setting properties once at construction time
- Removed `$tracekit` backward compatibility property (only `$apm` property used)
- Removed `gemvc/library` from require section (using PHPStan stubs for development)

**Removed:**
- `$request->tracekit` property assignment (backward compatibility removed)
- `TRACEKIT_MAX_STRING_LENGTH` environment variable fallback

**Fixed:**
- Operator precedence bug in boolean parsing
- Inconsistency between factory and abstract class parsing logic

**Documentation:**
- Updated README.md with constructor initialization details
- Updated environment variable documentation
- Updated provider examples

**Tests:**
- Added tests for config array support
- Added tests for boolean parsing (`'1'` and `'0'`)
- Added tests for environment variable fallback
- Added 28 new tests covering all previously uncovered methods in AbstractApm
- Test coverage: 51.75% lines, 52.27% methods (before Toolkit implementation)

---

## Version 1.0.0 - Initial Release

**Release Date:** 2025-01-XX

### Overview

`gemvc/apm-contracts` is the foundation package for Application Performance Monitoring (APM) providers in the GEMVC framework. This package provides the contracts, interfaces, and abstract base class that enable pluggable APM solutions.

### Features

- **ApmInterface** - Standard contract that all APM providers must implement
- **AbstractApm** - Base class with shared functionality (request handling, utilities, configuration)
- **ApmFactory** - Universal factory with dynamic provider instantiation (Open/Closed Principle)
- **Universal Pattern** - Works like `UniversalQueryExecuter` - abstracts provider implementation details
- **Auto-Discovery** - Providers are automatically discovered, no factory registration needed
- **Initialization Method** - `init()` method for setup/configuration via CLI/GUI tools
- **OpenTelemetry Compatible** - Follows OpenTelemetry standards for span kinds and status codes
- **Request Integration** - Seamless integration with GEMVC Request objects
- **Configuration Management** - Flexible configuration via environment variables or config arrays
- **Sampling Support** - Built-in sampling rate support for performance optimization
- **Type Safety** - Full PHP 8.2+ type hints and PHPStan level 9 compliance

### Requirements

- PHP >= 8.2
- gemvc/library >= 5.2

### Installation

```bash
composer require gemvc/apm-contracts
```

This package is automatically installed when you install GEMVC or any APM provider package.

### What's Included

#### Core Classes

1. **ApmInterface** (`src/Gemvc/Core/Apm/ApmInterface.php`)
   - Defines the contract for all APM providers
   - Includes OpenTelemetry span kind constants
   - Provides static utility methods

2. **AbstractApm** (`src/Gemvc/Core/Apm/AbstractApm.php`)
   - Base implementation with common functionality
   - Request object management
   - Configuration helpers
   - Sampling logic
   - Request body extraction utilities

3. **ApmFactory** (`src/Gemvc/Core/Apm/ApmFactory.php`)
   - Universal factory with dynamic provider instantiation
   - Auto-discovery of installed providers (no registration needed)
   - Follows Open/Closed Principle - add providers without modifying factory
   - Works like `UniversalQueryExecuter` - universal abstraction layer
   - Configuration validation

### Usage

#### Basic Usage

```php
use Gemvc\Core\Apm\ApmFactory;
use Gemvc\Http\Request;

// Create APM instance
$apm = ApmFactory::create($request);

if ($apm !== null && $apm->isEnabled()) {
    // Start a span
    $span = $apm->startSpan('database-query', [
        'db.query' => 'SELECT * FROM users'
    ]);
    
    // Your code here
    
    // End span
    $apm->endSpan($span, ['rows' => 10], ApmInterface::STATUS_OK);
}
```

#### Configuration

Configure via environment variables:

```env
APM_NAME="TraceKit"
APM_ENABLED="true"
APM_API_KEY="your-api-key"
APM_MAX_STRING_LENGTH="2000"
```

### Creating an APM Provider

See [README.md](README.md) for detailed instructions on creating your own APM provider package.

### Testing

The package includes comprehensive unit and integration tests:

```bash
# Run all tests
composer test

# Run specific test suites
composer test:unit
composer test:integration
composer test:protocol

# Generate coverage report
composer test:coverage
```

**Note:** Some tests are currently skipped pending gemvc/library 5.3+ update. See [TESTING_PROTOCOL.md](TESTING_PROTOCOL.md) for details.

### Code Quality

- **PHPStan Level 9** - Strictest static analysis
- **PHPUnit 10** - Comprehensive test coverage
- **PSR-4 Autoloading** - Standard namespace structure
- **Type Safety** - Full type hints throughout

### Documentation

- [README.md](README.md) - Complete package documentation
- [TESTING_PROTOCOL.md](TESTING_PROTOCOL.md) - Testing guidelines and protocol

### Breaking Changes

None - This is the initial release.

### Known Limitations

- Request class integration requires gemvc/library 5.3+ (currently using PHPStan ignore comments)
- Some tests are skipped until Request/Response classes are updated
- See [TESTING_PROTOCOL.md](TESTING_PROTOCOL.md) for implementation checklist

### Future Roadmap

- Full Request/Response integration (gemvc/library 5.3+)
- Additional APM provider implementations
- Enhanced configuration options
- Performance optimizations

### Related Packages

- [gemvc/apm-tracekit](https://github.com/gemvc/apm-tracekit) - TraceKit APM provider implementation
- [gemvc/library](https://github.com/gemvc/library) - GEMVC core framework

### Contributing

To add a new APM provider:

1. Create a new package following the structure in README.md
2. Submit a PR to `apm-contracts` to register your provider in `ApmFactory`
3. Update documentation with your provider's configuration

### Changelog

#### 1.0.0 (2025-12-31)

**Added:**
- Initial release
- ApmInterface with OpenTelemetry constants
- AbstractApm base class with shared functionality
- ApmFactory with universal dynamic provider instantiation (Open/Closed Principle)
- Auto-discovery of providers - no factory registration needed
- Comprehensive test suite
- PHPStan level 9 static analysis
- Full documentation

**Architecture:**
- Universal factory pattern (similar to UniversalQueryExecuter for databases)
- Dynamic provider instantiation based on APM_NAME environment variable
- Provider naming convention: `Gemvc\Core\Apm\Providers\{ProviderName}\{ProviderName}Provider`
- Follows SOLID principles (Open/Closed, Dependency Inversion, Liskov Substitution)

**Infrastructure:**
- PHPUnit 10 test framework
- PHPStan level 9 configuration
- Composer scripts for testing and analysis
- GitHub-ready package structure

### Support

- **Issues:** [GitHub Issues](https://github.com/gemvc/apm-contracts/issues)
- **Documentation:** [README.md](README.md)
- **Homepage:** [https://gemvc.de](https://gemvc.de)

### License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Part of the [GEMVC PHP Framework built for Microservices](https://gemvc.de) ecosystem.**

