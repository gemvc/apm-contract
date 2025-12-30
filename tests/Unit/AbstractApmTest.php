<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\Tests\Helpers\TestApmProvider;
use Gemvc\Core\Apm\Tests\Helpers\MockRequest;

class AbstractApmTest extends TestCase
{
    public function testIsEnabled(): void
    {
        $apm = new TestApmProvider(null, ['enabled' => true]);
        $this->assertTrue($apm->isEnabled());
        
        $apm = new TestApmProvider(null, ['enabled' => false]);
        $this->assertFalse($apm->isEnabled());
    }
    
    public function testShouldTraceFlags(): void
    {
        $apm = new TestApmProvider(null, [
            'trace_response' => true,
            'trace_db_query' => true,
            'trace_request_body' => true,
        ]);
        
        $this->assertTrue($apm->shouldTraceResponse());
        $this->assertTrue($apm->shouldTraceDbQuery());
        $this->assertTrue($apm->shouldTraceRequestBody());
    }
    
    /**
     * @group requires-request-update
     * @testdox Get request - requires Request class update
     */
    public function testGetRequest(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports APM integration.'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Get request body for tracing with POST - requires Request class update
     */
    public function testGetRequestBodyForTracingWithPost(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports body properties (post, put, patch).'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Request body tracing - requires Request class update
     */
    public function testGetRequestBodyForTracingIntegration(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports APM integration.'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Request APM property assignment - requires Request class update
     */
    public function testRequestApmPropertyAssignment(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports $request->apm property.'
        );
    }
}

