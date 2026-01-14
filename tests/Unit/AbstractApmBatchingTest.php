<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\Tests\Helpers\TestApmProvider;
use Gemvc\Core\Apm\AbstractApm;
use ReflectionClass;
use ReflectionMethod;

class AbstractApmBatchingTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up static properties between tests
        $reflection = new ReflectionClass(AbstractApm::class);
        
        // Clear batchedTraces
        $batchedTracesProperty = $reflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTracesProperty->setValue(null, []);
        
        // Clear lastBatchSendTime
        $lastBatchSendTimeProperty = $reflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, null);
        
        // Clear batchSendInterval
        $batchSendIntervalProperty = $reflection->getProperty('batchSendInterval');
        $batchSendIntervalProperty->setAccessible(true);
        $batchSendIntervalProperty->setValue(null, null);
        
        // Clean up environment variables
        unset($_ENV['APM_SEND_INTERVAL']);
        
        parent::tearDown();
    }
    
    public function testAddTraceToBatchWithValidPayload(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('addTraceToBatch');
        $method->setAccessible(true);
        
        $tracePayload = ['trace_id' => 'test-123', 'spans' => []];
        $method->invoke($apm, $tracePayload);
        
        // Verify trace was added to batch using reflection on AbstractApm
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertArrayHasKey('TestProvider', $batchedTraces);
        $this->assertCount(1, $batchedTraces['TestProvider']);
        $this->assertEquals($tracePayload, $batchedTraces['TestProvider'][0]);
    }
    
    public function testAddTraceToBatchWithEmptyPayloadDoesNothing(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('addTraceToBatch');
        $method->setAccessible(true);
        
        $method->invoke($apm, []);
        
        // Verify no trace was added
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        if (isset($batchedTraces['TestProvider'])) {
            $this->assertEmpty($batchedTraces['TestProvider']);
        }
    }
    
    public function testAddTraceToBatchInitializesLastBatchSendTime(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('addTraceToBatch');
        $method->setAccessible(true);
        
        // Clear lastBatchSendTime first
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, null);
        
        $tracePayload = ['trace_id' => 'test-123'];
        $method->invoke($apm, $tracePayload);
        
        // Verify lastBatchSendTime was initialized
        $lastBatchSendTime = $lastBatchSendTimeProperty->getValue();
        $this->assertNotNull($lastBatchSendTime);
        $this->assertIsFloat($lastBatchSendTime);
    }
    
    public function testAddMultipleTracesToBatch(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('addTraceToBatch');
        $method->setAccessible(true);
        
        $trace1 = ['trace_id' => 'test-1'];
        $trace2 = ['trace_id' => 'test-2'];
        $trace3 = ['trace_id' => 'test-3'];
        
        $method->invoke($apm, $trace1);
        $method->invoke($apm, $trace2);
        $method->invoke($apm, $trace3);
        
        // Verify all traces were added
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertCount(3, $batchedTraces['TestProvider']);
        $this->assertEquals($trace1, $batchedTraces['TestProvider'][0]);
        $this->assertEquals($trace2, $batchedTraces['TestProvider'][1]);
        $this->assertEquals($trace3, $batchedTraces['TestProvider'][2]);
    }
    
    public function testShouldSendBatchReturnsFalseWhenNoTraces(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true]);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('shouldSendBatch');
        $method->setAccessible(true);
        
        $result = $method->invoke($apm);
        $this->assertFalse($result);
    }
    
    public function testShouldSendBatchReturnsFalseWhenIntervalNotElapsed(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Set lastBatchSendTime to now (so interval hasn't elapsed)
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, microtime(true));
        
        // Check if should send
        $shouldSendMethod = $reflection->getMethod('shouldSendBatch');
        $shouldSendMethod->setAccessible(true);
        
        $result = $shouldSendMethod->invoke($apm);
        $this->assertFalse($result);
    }
    
    public function testShouldSendBatchReturnsTrueWhenIntervalElapsed(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Set lastBatchSendTime to 10 seconds ago (more than default 5 second interval)
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, microtime(true) - 10);
        
        // Check if should send
        $shouldSendMethod = $reflection->getMethod('shouldSendBatch');
        $shouldSendMethod->setAccessible(true);
        
        $result = $shouldSendMethod->invoke($apm);
        $this->assertTrue($result);
    }
    
    public function testShouldSendBatchUsesCustomIntervalFromEnv(): void
    {
        $_ENV['APM_SEND_INTERVAL'] = '2'; // 2 seconds
        
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Set lastBatchSendTime to 3 seconds ago (more than 2 second interval)
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, microtime(true) - 3);
        
        // Check if should send
        $shouldSendMethod = $reflection->getMethod('shouldSendBatch');
        $shouldSendMethod->setAccessible(true);
        
        $result = $shouldSendMethod->invoke($apm);
        $this->assertTrue($result);
    }
    
    public function testShouldSendBatchSetsLastBatchSendTimeIfNull(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Clear lastBatchSendTime
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, null);
        
        // Check if should send (should set lastBatchSendTime and return false)
        $shouldSendMethod = $reflection->getMethod('shouldSendBatch');
        $shouldSendMethod->setAccessible(true);
        
        $result = $shouldSendMethod->invoke($apm);
        $this->assertFalse($result);
        
        // Verify lastBatchSendTime was set
        $lastBatchSendTime = $lastBatchSendTimeProperty->getValue();
        $this->assertNotNull($lastBatchSendTime);
    }
    
    public function testSendBatchIfNeededDoesNothingWhenShouldNotSend(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Set lastBatchSendTime to now (so interval hasn't elapsed)
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $lastBatchSendTimeProperty = $abstractReflection->getProperty('lastBatchSendTime');
        $lastBatchSendTimeProperty->setAccessible(true);
        $lastBatchSendTimeProperty->setValue(null, microtime(true));
        
        // Call sendBatchIfNeeded
        $sendMethod = $reflection->getMethod('sendBatchIfNeeded');
        $sendMethod->setAccessible(true);
        $sendMethod->invoke($apm);
        
        // Verify traces are still in batch (not sent)
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertCount(1, $batchedTraces['TestProvider']);
    }
    
    public function testSendBatchClearsBatchAfterSuccessfulSend(): void
    {
        // This test would require mocking ApiCall, which is complex due to private method
        // We'll test the batch clearing logic separately
        $this->markTestSkipped('Requires ApiCall mocking - tested in integration tests');
    }
    
    public function testSendBatchWithEmptyTracesDoesNothing(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $method = $reflection->getMethod('sendBatch');
        $method->setAccessible(true);
        
        // Call sendBatch with no traces
        $method->invoke($apm);
        
        // Should not throw exception and should complete successfully
        $this->assertTrue(true);
    }
    
    public function testSendBatchWithEmptyPayloadClearsBatch(): void
    {
        // Create a provider that returns empty payload
        $apm = new class(null, ['enabled' => true, 'apm_name' => 'TestProvider']) extends TestApmProvider {
            protected function buildBatchPayload(array $traces): array
            {
                return []; // Return empty payload
            }
        };
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Send batch
        $sendMethod = $reflection->getMethod('sendBatch');
        $sendMethod->setAccessible(true);
        $sendMethod->invoke($apm);
        
        // Verify batch was cleared
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertEmpty($batchedTraces['TestProvider'] ?? []);
    }
    
    public function testSendBatchWithEmptyEndpointLogsErrorAndClearsBatch(): void
    {
        // Create a provider that returns empty endpoint
        $apm = new class(null, ['enabled' => true, 'apm_name' => 'TestProvider']) extends TestApmProvider {
            protected function getBatchEndpoint(): string
            {
                return ''; // Return empty endpoint
            }
        };
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Send batch
        $sendMethod = $reflection->getMethod('sendBatch');
        $sendMethod->setAccessible(true);
        $sendMethod->invoke($apm);
        
        // Verify batch was cleared (even on error)
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertEmpty($batchedTraces['TestProvider'] ?? []);
    }
    
    public function testForceSendBatchCallsSendBatch(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        
        // Add a trace
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $addMethod->invoke($apm, ['trace_id' => 'test-123']);
        
        // Force send batch
        $forceSendMethod = $reflection->getMethod('forceSendBatch');
        $forceSendMethod->setAccessible(true);
        
        // This should call sendBatch, which will attempt to send
        // Since we can't easily mock ApiCall, we just verify it doesn't throw
        try {
            $forceSendMethod->invoke($apm);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Expected if endpoint is not reachable, but method should be called
            $this->assertStringContainsString('Batch send failed', $e->getMessage());
        }
    }
    
    public function testBatchSendIntervalMinimumIsOneSecond(): void
    {
        $_ENV['APM_SEND_INTERVAL'] = '0'; // Try to set to 0
        
        $apm = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'TestProvider']);
        
        $reflection = new ReflectionClass($apm);
        $getIntervalMethod = $reflection->getMethod('getBatchSendInterval');
        $getIntervalMethod->setAccessible(true);
        
        $interval = $getIntervalMethod->invoke($apm);
        $this->assertGreaterThanOrEqual(1, $interval);
    }
    
    public function testBatchSendIntervalUsesDefaultWhenNotSet(): void
    {
        unset($_ENV['APM_SEND_INTERVAL']);
        
        $apm = new TestApmProvider(null, ['enabled' => true]);
        
        $reflection = new ReflectionClass($apm);
        $getIntervalMethod = $reflection->getMethod('getBatchSendInterval');
        $getIntervalMethod->setAccessible(true);
        
        $interval = $getIntervalMethod->invoke($apm);
        $this->assertEquals(5, $interval); // Default is 5 seconds
    }
    
    public function testBatchSendIntervalUsesDefaultForInvalidValue(): void
    {
        $_ENV['APM_SEND_INTERVAL'] = 'invalid';
        
        $apm = new TestApmProvider(null, ['enabled' => true]);
        
        $reflection = new ReflectionClass($apm);
        $getIntervalMethod = $reflection->getMethod('getBatchSendInterval');
        $getIntervalMethod->setAccessible(true);
        
        $interval = $getIntervalMethod->invoke($apm);
        $this->assertEquals(5, $interval); // Should use default
    }
    
    public function testMultipleProvidersHaveSeparateBatches(): void
    {
        $apm1 = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'Provider1']);
        $apm2 = new TestApmProvider(null, ['enabled' => true, 'apm_name' => 'Provider2']);
        
        $reflection = new ReflectionClass($apm1);
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        
        $trace1 = ['trace_id' => 'provider1-trace'];
        $trace2 = ['trace_id' => 'provider2-trace'];
        
        $addMethod->invoke($apm1, $trace1);
        $addMethod->invoke($apm2, $trace2);
        
        // Verify each provider has its own batch
        $abstractReflection = new ReflectionClass(AbstractApm::class);
        $batchedTracesProperty = $abstractReflection->getProperty('batchedTraces');
        $batchedTracesProperty->setAccessible(true);
        $batchedTraces = $batchedTracesProperty->getValue();
        $this->assertArrayHasKey('Provider1', $batchedTraces);
        $this->assertArrayHasKey('Provider2', $batchedTraces);
        $this->assertCount(1, $batchedTraces['Provider1']);
        $this->assertCount(1, $batchedTraces['Provider2']);
        $this->assertEquals($trace1, $batchedTraces['Provider1'][0]);
        $this->assertEquals($trace2, $batchedTraces['Provider2'][0]);
    }
    
    public function testBuildBatchPayloadIsCalledWithTraces(): void
    {
        $tracesPassed = null;
        
        // Create a provider that captures traces passed to buildBatchPayload
        $apm = new class(null, ['enabled' => true, 'apm_name' => 'TestProvider']) extends TestApmProvider {
            public $capturedTraces = null;
            
            protected function buildBatchPayload(array $traces): array
            {
                $this->capturedTraces = $traces;
                return parent::buildBatchPayload($traces);
            }
        };
        
        $reflection = new ReflectionClass($apm);
        
        // Add traces
        $addMethod = $reflection->getMethod('addTraceToBatch');
        $addMethod->setAccessible(true);
        $trace1 = ['trace_id' => 'test-1'];
        $trace2 = ['trace_id' => 'test-2'];
        $addMethod->invoke($apm, $trace1);
        $addMethod->invoke($apm, $trace2);
        
        // Send batch
        $sendMethod = $reflection->getMethod('sendBatch');
        $sendMethod->setAccessible(true);
        
        try {
            $sendMethod->invoke($apm);
        } catch (\Exception $e) {
            // Expected if endpoint not reachable
        }
        
        // Verify buildBatchPayload was called with correct traces
        $this->assertNotNull($apm->capturedTraces);
        $this->assertCount(2, $apm->capturedTraces);
        $this->assertEquals($trace1, $apm->capturedTraces[0]);
        $this->assertEquals($trace2, $apm->capturedTraces[1]);
    }
}
