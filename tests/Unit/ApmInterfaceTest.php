<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\ApmInterface;

class ApmInterfaceTest extends TestCase
{
    public function testInterfaceConstants(): void
    {
        $this->assertEquals(0, ApmInterface::SPAN_KIND_UNSPECIFIED);
        $this->assertEquals(1, ApmInterface::SPAN_KIND_INTERNAL);
        $this->assertEquals(2, ApmInterface::SPAN_KIND_SERVER);
        $this->assertEquals(3, ApmInterface::SPAN_KIND_CLIENT);
        $this->assertEquals(4, ApmInterface::SPAN_KIND_PRODUCER);
        $this->assertEquals(5, ApmInterface::SPAN_KIND_CONSUMER);
        $this->assertEquals('OK', ApmInterface::STATUS_OK);
        $this->assertEquals('ERROR', ApmInterface::STATUS_ERROR);
    }
    
    public function testDetermineStatusFromHttpCode(): void
    {
        // Use AbstractApm since it implements the static method
        $this->assertEquals(ApmInterface::STATUS_OK, \Gemvc\Core\Apm\AbstractApm::determineStatusFromHttpCode(200));
        $this->assertEquals(ApmInterface::STATUS_OK, \Gemvc\Core\Apm\AbstractApm::determineStatusFromHttpCode(301));
        $this->assertEquals(ApmInterface::STATUS_ERROR, \Gemvc\Core\Apm\AbstractApm::determineStatusFromHttpCode(400));
        $this->assertEquals(ApmInterface::STATUS_ERROR, \Gemvc\Core\Apm\AbstractApm::determineStatusFromHttpCode(500));
    }
    
    public function testLimitStringForTracing(): void
    {
        // Use AbstractApm since it implements the static method
        $short = 'Hello World';
        $this->assertEquals($short, \Gemvc\Core\Apm\AbstractApm::limitStringForTracing($short));
        
        $long = str_repeat('a', 3000);
        $limited = \Gemvc\Core\Apm\AbstractApm::limitStringForTracing($long);
        $this->assertLessThanOrEqual(2000, strlen($limited));
        $this->assertStringEndsWith('...', $limited);
    }
    
    public function testLimitStringForTracingWithCustomMaxLength(): void
    {
        $_ENV['APM_MAX_STRING_LENGTH'] = '500';
        
        $long = str_repeat('a', 1000);
        $limited = \Gemvc\Core\Apm\AbstractApm::limitStringForTracing($long);
        $this->assertLessThanOrEqual(500, strlen($limited));
        
        unset($_ENV['APM_MAX_STRING_LENGTH']);
    }
}

