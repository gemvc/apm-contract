<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\ApmToolkitInterface;
use Gemvc\Core\Apm\Tests\Helpers\TestApmToolkit;

class ApmToolkitInterfaceTest extends TestCase
{
    public function testInterfaceConstants(): void
    {
        // Verify interface exists and can be implemented
        $this->assertTrue(interface_exists(ApmToolkitInterface::class));
        
        $toolkit = new TestApmToolkit();
        $this->assertInstanceOf(ApmToolkitInterface::class, $toolkit);
    }
    
    public function testSetApiKey(): void
    {
        $toolkit = new TestApmToolkit();
        $result = $toolkit->setApiKey('test-api-key');
        
        $this->assertSame($toolkit, $result);
    }
    
    public function testSetServiceName(): void
    {
        $toolkit = new TestApmToolkit();
        $result = $toolkit->setServiceName('test-service');
        
        $this->assertSame($toolkit, $result);
    }
    
    public function testInterfaceMethodsExist(): void
    {
        $reflection = new \ReflectionClass(ApmToolkitInterface::class);
        
        $requiredMethods = [
            'setApiKey',
            'setServiceName',
            'registerService',
            'verifyCode',
            'getStatus',
            'sendHeartbeat',
            'sendHeartbeatAsync',
            'listHealthChecks',
            'getMetrics',
            'getAlertsSummary',
            'getActiveAlerts',
            'createWebhook',
            'listWebhooks',
            'getSubscription',
            'listPlans',
            'createCheckoutSession',
        ];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Interface must have method: $method"
            );
        }
    }
}

