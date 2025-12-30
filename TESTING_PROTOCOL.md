# Testing Protocol for APM Contracts

## Overview

This document outlines the testing protocol for `gemvc/apm-contracts` package. Some tests are currently skipped because they require updates to `gemvc/library` (specifically the `Request` and `Response` classes). Once `gemvc/library` version 5.3+ is released with APM support, these tests should be implemented.

## Required Updates in gemvc/library

### Request Class Requirements

The `Gemvc\Http\Request` class must support:

1. **APM Property Assignment**
   ```php
   $request->apm = $apmInstance;  // Primary property
   $request->tracekit = $apmInstance;  // Backward compatibility (optional)
   ```

2. **Method Requirements**
   - `getMethod(): string` - HTTP method (GET, POST, etc.)
   - `getUri(): string` - Request URI
   - `getHeader(string $name): ?string` - Get header value
   - `getServiceName(): string` - Service name from route
   - `getMethodName(): string` - Method name from route

3. **Body Data Properties**
   - `$request->post` - POST body data (array)
   - `$request->put` - PUT body data (array)
   - `$request->patch` - PATCH body data (array)

### Response Class Requirements (Future)

The `Gemvc\Http\Response` class should support:

1. **APM Integration** (for response tracing)
   - Access to `$request->apm` for tracing response data
   - Status code access for span status determination

## Test Implementation Protocol

### Phase 1: Current Tests (Can be implemented now)

✅ **Unit Tests - No Dependencies**
- `ApmInterfaceTest` - Interface constants and static methods
- `AbstractApmTest::testIsEnabled()` - Basic enabled/disabled
- `AbstractApmTest::testShouldTraceFlags()` - Configuration flags
- `ApmFactoryTest::testCreateReturnsNullWhenDisabled()` - Factory disabled state
- `ApmFactoryTest::testIsEnabledReturnsFalseWhenDisabled()` - Factory enabled check

✅ **Integration Tests - Using MockRequest**
- `AbstractApmIntegrationTest::testFullLifecycleWithMockRequest()` - Full lifecycle with mock

### Phase 2: Tests Requiring gemvc/library 5.3+

#### 2.1 Request Integration Tests

**Test: `AbstractApmTest::testRequestApmPropertyAssignment()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: `$request->apm` property must be assignable
- **Implementation**:
  ```php
  public function testRequestApmPropertyAssignment(): void
  {
      $request = new \Gemvc\Http\Request(/* ... */);
      $apm = new TestApmProvider($request);
      
      $this->assertSame($apm, $request->apm);
      $this->assertSame($apm, $request->tracekit); // Backward compatibility
  }
  ```

**Test: `AbstractApmTest::testGetRequestBodyForTracingIntegration()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Request class must have `post`, `put`, `patch` properties
- **Implementation**:
  ```php
  public function testGetRequestBodyForTracingIntegration(): void
  {
      $request = new \Gemvc\Http\Request('POST', '/api/test', [], ['name' => 'test']);
      $apm = new TestApmProvider($request, ['trace_request_body' => true]);
      
      $reflection = new \ReflectionClass($apm);
      $method = $reflection->getMethod('getRequestBodyForTracing');
      $method->setAccessible(true);
      
      $result = $method->invoke($apm);
      $this->assertNotNull($result);
      $this->assertStringContainsString('test', $result);
  }
  ```

**Test: `AbstractApmTest::testInitializeRootTraceWithRealRequest()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Request class must implement all required methods
- **Implementation**:
  ```php
  public function testInitializeRootTraceWithRealRequest(): void
  {
      $request = new \Gemvc\Http\Request(
          'POST',
          '/api/users',
          ['User-Agent' => 'TestAgent'],
          ['name' => 'John']
      );
      
      $apm = new TestApmProvider($request, ['enabled' => true]);
      
      $this->assertNotNull($apm->getTraceId());
      $this->assertNotEmpty($apm->spans);
      $this->assertSame('http-request', $apm->spans[0]['operation_name']);
  }
  ```

#### 2.2 Factory Integration Tests

**Test: `ApmFactoryTest::testCreateWithRequest()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Factory must work with real Request objects
- **Implementation**:
  ```php
  public function testCreateWithRequest(): void
  {
      $_ENV['APM_NAME'] = 'TraceKit';
      $_ENV['APM_ENABLED'] = 'true';
      $_ENV['TRACEKIT_API_KEY'] = 'test-key';
      
      $request = new \Gemvc\Http\Request();
      $apm = ApmFactory::create($request);
      
      $this->assertNotNull($apm);
      $this->assertInstanceOf(\Gemvc\Core\Apm\ApmInterface::class, $apm);
      $this->assertSame($apm, $request->apm);
  }
  ```

**Test: `ApmFactoryTest::testCreateWithConfigOverride()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Factory must accept config overrides
- **Implementation**:
  ```php
  public function testCreateWithConfigOverride(): void
  {
      $_ENV['APM_NAME'] = 'TraceKit';
      $_ENV['APM_ENABLED'] = 'true';
      
      $request = new \Gemvc\Http\Request();
      $config = ['enabled' => false];
      
      $apm = ApmFactory::create($request, $config);
      
      $this->assertNotNull($apm);
      $this->assertFalse($apm->isEnabled());
  }
  ```

#### 2.3 Full Integration Tests

**Test: `AbstractApmIntegrationTest::testFullLifecycleWithRealRequest()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Complete Request class implementation
- **Implementation**:
  ```php
  public function testFullLifecycleWithRealRequest(): void
  {
      $request = new \Gemvc\Http\Request(
          'POST',
          '/api/users',
          ['User-Agent' => 'TestAgent/1.0'],
          ['name' => 'John', 'email' => 'john@example.com']
      );
      
      $apm = new TestApmProvider($request, [
          'enabled' => true,
          'trace_request_body' => true,
          'trace_response' => true,
      ]);
      
      // Verify APM is stored in request
      $this->assertSame($apm, $request->apm);
      
      // Verify root trace initialized
      $this->assertNotNull($apm->getTraceId());
      
      // Test span operations
      $span = $apm->startSpan('database-query');
      $apm->endSpan($span);
      
      // Test exception handling
      $exception = new \RuntimeException('Test error');
      $apm->recordException($span, $exception);
      
      // Test flush
      $apm->flush();
      $this->assertTrue($apm->flushed);
  }
  ```

**Test: `AbstractApmIntegrationTest::testRequestBodyTracingWithRealRequest()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+
- **Requirements**: Request body properties (post, put, patch)
- **Implementation**:
  ```php
  public function testRequestBodyTracingWithRealRequest(): void
  {
      $body = ['name' => 'Test', 'value' => 123];
      $request = new \Gemvc\Http\Request('POST', '/api/test', [], $body);
      
      $apm = new TestApmProvider($request, ['trace_request_body' => true]);
      
      $reflection = new \ReflectionClass($apm);
      $method = $reflection->getMethod('getRequestBodyForTracing');
      $method->setAccessible(true);
      
      $result = $method->invoke($apm);
      $this->assertNotNull($result);
      $this->assertStringContainsString('Test', $result);
      $this->assertStringContainsString('123', $result);
  }
  ```

#### 2.4 Response Integration Tests (Future)

**Test: `AbstractApmIntegrationTest::testResponseTracing()`**
- **Status**: ⏸️ Skipped until gemvc/library 5.3+ (Response class update)
- **Requirements**: Response class must support APM integration
- **Note**: This test should be added when Response class is updated

## Implementation Checklist

When `gemvc/library` version 5.3+ is released:

### Step 1: Update Dependencies
- [ ] Update `composer.json` to require `gemvc/library: ^5.3`
- [ ] Run `composer update gemvc/library`
- [ ] Verify Request class has `apm` property support
- [ ] Verify Request class implements all required methods

### Step 2: Remove Mock Request
- [ ] Remove or deprecate `tests/Helpers/MockRequest.php`
- [ ] Update all tests to use real `\Gemvc\Http\Request` class
- [ ] Remove `@group requires-request-update` annotations

### Step 3: Enable Skipped Tests
- [ ] Remove `markTestSkipped()` calls from all Phase 2 tests
- [ ] Implement `AbstractApmTest::testRequestApmPropertyAssignment()`
- [ ] Implement `AbstractApmTest::testGetRequestBodyForTracingIntegration()`
- [ ] Implement `AbstractApmTest::testInitializeRootTraceWithRealRequest()`
- [ ] Implement `ApmFactoryTest::testCreateWithRequest()`
- [ ] Implement `ApmFactoryTest::testCreateWithConfigOverride()`
- [ ] Implement `AbstractApmIntegrationTest::testFullLifecycleWithRealRequest()`
- [ ] Implement `AbstractApmIntegrationTest::testRequestBodyTracingWithRealRequest()`

### Step 4: Add New Tests
- [ ] Test Request property assignment edge cases
- [ ] Test Request with different HTTP methods (GET, POST, PUT, PATCH, DELETE)
- [ ] Test Request with various header combinations
- [ ] Test Request with empty/null body data
- [ ] Test Request with large body data (performance)
- [ ] Test multiple APM instances with same Request

### Step 5: Response Integration (When Available)
- [ ] Add Response class integration tests
- [ ] Test response tracing functionality
- [ ] Test status code to span status mapping

### Step 6: Validation
- [ ] Run full test suite: `composer test`
- [ ] Verify all tests pass
- [ ] Check test coverage (aim for >80%)
- [ ] Update documentation if needed

## Test Groups

Tests are organized using PHPUnit groups:

- `@group requires-request-update` - Tests requiring Request class updates
- `@group requires-response-update` - Tests requiring Response class updates (future)
- `@group integration` - Integration tests
- `@group unit` - Unit tests
- `@group protocol` - Protocol tests (templates for future implementation)

Run specific groups:
```bash
# Run only tests that don't require updates
phpunit --exclude-group requires-request-update

# Run only tests requiring Request updates (after gemvc/library 5.3+)
phpunit --group requires-request-update
```

## Version Compatibility Matrix

| gemvc/apm-contracts | gemvc/library | Test Status |
|---------------------|---------------|-------------|
| 1.0.0               | ^5.2          | Phase 1 tests only |
| 1.1.0+               | ^5.3          | All tests (Phase 1 + Phase 2) |

## Running Tests

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration

# Run protocol tests (incomplete tests)
composer test:protocol

# Generate coverage report
composer test:coverage
```

## Notes

- All skipped tests include clear messages explaining why they're skipped
- Mock Request class is provided for current testing needs
- Protocol will be updated as new requirements are identified
- Response class integration tests will be added in a future update

## Contact

For questions about this protocol or test implementation, please refer to:
- GitHub Issues: https://github.com/gemvc/apm-contracts/issues
- Documentation: See README.md

