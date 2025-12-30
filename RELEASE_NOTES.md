# Release Notes

## Version 1.0.0 - Initial Release

**Release Date:** 2025-01-XX

### Overview

`gemvc/apm-contracts` is the foundation package for Application Performance Monitoring (APM) providers in the GEMVC framework. This package provides the contracts, interfaces, and abstract base class that enable pluggable APM solutions.

### Features

- **ApmInterface** - Standard contract that all APM providers must implement
- **AbstractApm** - Base class with shared functionality (request handling, utilities, configuration)
- **ApmFactory** - Factory pattern for creating APM provider instances based on configuration
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
   - Factory for creating APM instances
   - Auto-discovery of installed providers
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

#### 1.0.0 (2025-01-XX)

**Added:**
- Initial release
- ApmInterface with OpenTelemetry constants
- AbstractApm base class with shared functionality
- ApmFactory for provider instantiation
- Comprehensive test suite
- PHPStan level 9 static analysis
- Full documentation

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

