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
    
    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
        $_ENV['APM_ENABLED'] = 'false';
        
        $this->assertFalse(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsFalseForUnknownProvider(): void
    {
        $_ENV['APM_NAME'] = 'UnknownProvider';
        $_ENV['APM_ENABLED'] = 'true';
        
        $this->assertFalse(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsTrueForTraceKitWhenConfigured(): void
    {
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = 'true';
        $_ENV['TRACEKIT_API_KEY'] = 'test-key';
        
        $this->assertTrue(ApmFactory::isEnabled());
    }
    
    public function testIsEnabledReturnsTrueForTraceKitWithUnifiedApiKey(): void
    {
        $_ENV['APM_NAME'] = 'TraceKit';
        $_ENV['APM_ENABLED'] = 'true';
        $_ENV['APM_API_KEY'] = 'test-key';
        
        $this->assertTrue(ApmFactory::isEnabled());
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

