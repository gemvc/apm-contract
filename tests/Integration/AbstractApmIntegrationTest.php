<?php
namespace Gemvc\Core\Apm\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\Tests\Helpers\TestApmProvider;
use Gemvc\Core\Apm\Tests\Helpers\MockRequest;

class AbstractApmIntegrationTest extends TestCase
{
    /**
     * @group requires-request-update
     * @testdox Full lifecycle with mock request - requires Request class update
     */
    public function testFullLifecycleWithMockRequest(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports APM integration.'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Full lifecycle with real Request - requires Request class update
     */
    public function testFullLifecycleWithRealRequest(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports $request->apm property.'
        );
    }
    
    /**
     * @group requires-request-update
     * @testdox Request body tracing with real Request - requires Request class update
     */
    public function testRequestBodyTracingWithRealRequest(): void
    {
        $this->markTestSkipped(
            'This test requires Request class to be updated in gemvc/library. ' .
            'Will be enabled once Request class supports body properties (post, put, patch).'
        );
    }
}

