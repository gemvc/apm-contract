# Release Notes

## Version 1.2.0 - Performance & Configuration Improvements

**Release Date:** 2025-12-31

### Overview

This release focuses on performance optimizations and enhanced configuration flexibility. Properties are now set directly in the constructor, reducing overhead and simplifying the initialization process.

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

### Changes

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

### Documentation Updates

- Updated README.md with detailed information about:
  - Constructor property initialization
  - Config array precedence
  - Boolean value formats accepted
  - Environment variable defaults
- Updated provider examples to reflect new initialization pattern

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
- **59 tests total, 104 assertions, 6 skipped** (pending gemvc/library updates)
- **Test coverage improved: 88.99% lines (was 52.29%), 84.21% methods (was 57.89%)**

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

### Breaking Changes

- **Removed `$tracekit` backward compatibility property** - Only `$request->apm` is now used (gemvc/library 5.2.2+)
- **Removed `gemvc/library` from require** - Package now uses PHPStan stubs for development to avoid circular dependencies

### Bug Fixes

- Fixed operator precedence issue in boolean parsing
- Fixed inconsistency between `ApmFactory::isEnabled()` and `AbstractApm` constructor parsing

### Changelog

#### 1.1.0 (2025-01-XX)

**Added:**
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
- Added 28 new tests covering all previously uncovered methods
- Test coverage improved from 52.29% to 88.99% lines, 57.89% to 84.21% methods

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

