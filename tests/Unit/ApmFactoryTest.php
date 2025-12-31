<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\ApmFactory;

class ApmFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear environment
        unset($_ENV['APM_NAME']);
        unset($_ENV['APM_ENABLED']);
        unset($_ENV['TRACEKIT_API_KEY']);
        unset($_ENV['APM_API_KEY']);
    }
    
    protected function tearDown(): void
    {
        // Clean up
        unset($_ENV['APM_NAME']);
        unset($_ENV['APM_ENABLED']);
        unset($_ENV['TRACEKIT_API_KEY']);
        unset($_ENV['APM_API_KEY']);
    }
    
    public function testCreateReturnsNullWhenDisabled(): void
    {
        $_ENV['APM_ENABLED'] = 'false';
        
        $result = ApmFactory::create();
        
        $this->assertNull($result);
    }
    
    public function testIsEnabledReturnsNullWhenDisabled(): void
    {
        $_ENV['APM_ENABLED'] = 'false';
        
        $this->assertNull(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsNullWhenNameNotSet(): void
    {
        unset($_ENV['APM_NAME']);
        $_ENV['APM_ENABLED'] = 'true';
        
        $this->assertNull(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsNullWhenNameEmpty(): void
    {
        $_ENV['APM_NAME'] = '';
        $_ENV['APM_ENABLED'] = 'true';
        
        $this->assertNull(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsProviderNameWhenEnabled(): void
    {
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = 'true';
        
        $result = ApmFactory::isEnabled();
        $this->assertSame('TraceKit', $result);
    }
    
    public function testIsEnabledReturnsProviderNameWithDefaultEnabled(): void
    {
        $_ENV['APM_NAME'] = 'Datadog';
        unset($_ENV['APM_ENABLED']); // Default should be 'true'
        
        $result = ApmFactory::isEnabled();
        $this->assertSame('Datadog', $result);
    }
    
    public function testIsEnabledAcceptsOneAsTrue(): void
    {
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = '1';
        
        $result = ApmFactory::isEnabled();
        $this->assertSame('TraceKit', $result);
    }
    
    public function testIsEnabledRejectsZeroAsFalse(): void
    {
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = '0';
        
        $result = ApmFactory::isEnabled();
        $this->assertNull($result);
    }
    
    public function testCreateReturnsNullForNonExistentProvider(): void
    {
        $_ENV['APM_NAME'] = 'NonExistentProvider';
        $_ENV['APM_ENABLED'] = 'true';
        
        $result = ApmFactory::create();
        
        $this->assertNull($result);
    }
    
    public function testCreateHandlesInvalidProviderName(): void
    {
        $_ENV['APM_NAME'] = 'invalid-name-with-special-chars!@#';
        $_ENV['APM_ENABLED'] = 'true';
        
        $result = ApmFactory::create();
        
        // Should gracefully handle invalid names
        $this->assertNull($result);
    }
    
    public function testCreateWithProviderName(): void
    {
        // Test that provider name is used directly (standardized through init process)
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = 'true';
        
        $result = ApmFactory::create();
        // Should not throw error, even if provider not installed
        $this->assertNull($result); // Will be null if provider not installed
    }
    
    /**
     * @group requires-request-update
     * @testdox Factory creation with Request - requires Request class update
     */
    public function testCreateWithRequest(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports APM integration.'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Factory creation with config override - requires Request class update
     */
    public function testCreateWithConfigOverride(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports APM integration.'
        );
    }
}

