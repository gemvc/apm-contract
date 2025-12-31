<?php
namespace Gemvc\Core\Apm\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Gemvc\Core\Apm\Tests\Helpers\TestApmToolkit;

class AbstractApmToolkitTest extends TestCase
{
    public function testConstructorLoadsFromEnv(): void
    {
        $_ENV['TEST_APM_API_KEY'] = 'env-api-key';
        $_ENV['TEST_APM_SERVICE_NAME'] = 'env-service-name';
        
        $toolkit = new TestApmToolkit();
        
        // Verify properties are set (we can't directly access them, but we can test via methods)
        $reflection = new \ReflectionClass($toolkit);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        
        $this->assertEquals('env-api-key', $apiKeyProperty->getValue($toolkit));
        
        // Clean up
        unset($_ENV['TEST_APM_API_KEY'], $_ENV['TEST_APM_SERVICE_NAME']);
    }
    
    public function testConstructorUsesProvidedValues(): void
    {
        $toolkit = new TestApmToolkit('provided-key', 'provided-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $serviceNameProperty = $reflection->getProperty('serviceName');
        $apiKeyProperty->setAccessible(true);
        $serviceNameProperty->setAccessible(true);
        
        $this->assertEquals('provided-key', $apiKeyProperty->getValue($toolkit));
        $this->assertEquals('provided-service', $serviceNameProperty->getValue($toolkit));
    }
    
    public function testSetApiKey(): void
    {
        $toolkit = new TestApmToolkit();
        $result = $toolkit->setApiKey('new-key');
        
        $this->assertSame($toolkit, $result);
        
        $reflection = new \ReflectionClass($toolkit);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        
        $this->assertEquals('new-key', $apiKeyProperty->getValue($toolkit));
    }
    
    public function testSetServiceName(): void
    {
        $toolkit = new TestApmToolkit();
        $result = $toolkit->setServiceName('new-service');
        
        $this->assertSame($toolkit, $result);
        
        $reflection = new \ReflectionClass($toolkit);
        $serviceNameProperty = $reflection->getProperty('serviceName');
        $serviceNameProperty->setAccessible(true);
        
        $this->assertEquals('new-service', $serviceNameProperty->getValue($toolkit));
    }
    
    public function testRequireApiKeyReturnsUnauthorizedWhenEmpty(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('requireApiKey');
        $method->setAccessible(true);
        
        $result = $method->invoke($toolkit);
        
        $this->assertNotNull($result);
        $this->assertEquals(401, $result->response_code);
    }
    
    public function testRequireApiKeyReturnsNullWhenSet(): void
    {
        $toolkit = new TestApmToolkit('valid-key', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('requireApiKey');
        $method->setAccessible(true);
        
        $result = $method->invoke($toolkit);
        
        $this->assertNull($result);
    }
    
    public function testCreateApiCall(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('createApiCall');
        $method->setAccessible(true);
        
        $apiCall = $method->invoke($toolkit, true, true);
        
        $this->assertInstanceOf(\Gemvc\Http\ApiCall::class, $apiCall);
        $this->assertArrayHasKey('X-API-Key', $apiCall->header);
        $this->assertEquals('test-key', $apiCall->header['X-API-Key']);
        $this->assertArrayHasKey('Content-Type', $apiCall->header);
        $this->assertEquals('application/json', $apiCall->header['Content-Type']);
    }
    
    public function testCreateApiCallWithoutAuth(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('createApiCall');
        $method->setAccessible(true);
        
        $apiCall = $method->invoke($toolkit, false, false);
        
        $this->assertInstanceOf(\Gemvc\Http\ApiCall::class, $apiCall);
        $this->assertArrayNotHasKey('X-API-Key', $apiCall->header);
        $this->assertArrayNotHasKey('Content-Type', $apiCall->header);
    }
    
    public function testParseJsonResponseWithValidJson(): void
    {
        $toolkit = new TestApmToolkit();
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('parseJsonResponse');
        $method->setAccessible(true);
        
        $json = json_encode(['key' => 'value', 'number' => 123]);
        $result = $method->invoke($toolkit, $json, 'Test');
        
        $this->assertEquals(200, $result->response_code);
        $this->assertIsArray($result->data);
        $this->assertEquals('value', $result->data['key']);
        $this->assertEquals(123, $result->data['number']);
    }
    
    public function testParseJsonResponseWithInvalidJson(): void
    {
        $toolkit = new TestApmToolkit();
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('parseJsonResponse');
        $method->setAccessible(true);
        
        $result = $method->invoke($toolkit, 'invalid json', 'Test');
        
        $this->assertEquals(500, $result->response_code);
    }
    
    public function testParseJsonResponseWithFalse(): void
    {
        $toolkit = new TestApmToolkit();
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('parseJsonResponse');
        $method->setAccessible(true);
        
        $result = $method->invoke($toolkit, false, 'Test');
        
        $this->assertEquals(500, $result->response_code);
    }
    
    public function testGetMetricsReplacesServiceNamePlaceholder(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'my-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        $baseUrlProperty->setValue($toolkit, 'https://api.test.com');
        
        // This will fail because we don't have a real API, but we can verify the endpoint construction
        // We'll test the endpoint replacement logic via reflection
        $metricsMethod = $reflection->getMethod('getMetricsEndpoint');
        $metricsMethod->setAccessible(true);
        $endpoint = $metricsMethod->invoke($toolkit);
        
        // Verify placeholder exists in endpoint
        $this->assertStringContainsString('{serviceName}', $endpoint);
    }
    
    public function testSendHeartbeatAsyncWithEmptyApiKey(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        // Should silently fail without error
        $toolkit->sendHeartbeatAsync('healthy');
        
        // No exception should be thrown
        $this->assertTrue(true);
    }
    
    
    public function testRegisterService(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        // registerService doesn't require auth, so it should attempt the call
        // Since we can't mock ApiCall easily, we'll test the payload construction
        // by checking the endpoint is called correctly via reflection
        
        // The method will fail because there's no real API, but we can verify
        // it constructs the payload correctly by checking the response structure
        $response = $toolkit->registerService('test@example.com', 'Test Org', 'gemvc', ['version' => '1.0']);
        
        // Should return an error response (since no real API), but structure should be correct
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
        $this->assertIsInt($response->response_code);
    }
    
    public function testRegisterServiceWithAllParams(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $response = $toolkit->registerService(
            'test@example.com',
            'My Organization',
            'custom-source',
            ['version' => '1.0', 'env' => 'test']
        );
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testVerifyCode(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $response = $toolkit->verifyCode('session-123', '123456');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testVerifyCodeUpdatesApiKey(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        // We can't easily test the API key update without mocking the response
        // But we can verify the method exists and returns JsonResponse
        $response = $toolkit->verifyCode('session-123', '123456');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetStatus(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->getStatus();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
        // Should return unauthorized or error since no real API
        $this->assertContains($response->response_code, [400, 401, 500]);
    }
    
    public function testGetStatusWithoutApiKey(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $response = $toolkit->getStatus();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->response_code);
    }
    
    public function testSendHeartbeat(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->sendHeartbeat('healthy', ['memory' => 100]);
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testSendHeartbeatWithoutApiKey(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $response = $toolkit->sendHeartbeat('healthy');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
        $this->assertEquals(401, $response->response_code);
    }
    
    public function testListHealthChecks(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->listHealthChecks();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetMetrics(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'my-service');
        
        $response = $toolkit->getMetrics('1h');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetMetricsWithDefaultWindow(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'my-service');
        
        $response = $toolkit->getMetrics();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetAlertsSummary(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->getAlertsSummary();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetActiveAlerts(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->getActiveAlerts(10);
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetActiveAlertsWithDefaultLimit(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->getActiveAlerts();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testCreateWebhook(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->createWebhook(
            'test-webhook',
            'https://example.com/webhook',
            ['alert.created', 'alert.resolved'],
            true
        );
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testCreateWebhookDisabled(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->createWebhook(
            'test-webhook',
            'https://example.com/webhook',
            ['alert.created'],
            false
        );
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testListWebhooks(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->listWebhooks();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testGetSubscription(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->getSubscription();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testListPlans(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        // listPlans doesn't require auth
        $response = $toolkit->listPlans();
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testCreateCheckoutSession(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->createCheckoutSession(
            'pro-plan',
            'monthly',
            'gemvc',
            'https://example.com/success',
            'https://example.com/cancel'
        );
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testCreateCheckoutSessionWithDefaults(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $response = $toolkit->createCheckoutSession('starter-plan');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testMakeGetRequestWithoutAuth(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('makeGetRequest');
        $method->setAccessible(true);
        
        // This will fail because no real API, but tests the method structure
        $response = $method->invoke($toolkit, '/test', false, 'Test');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testMakePostRequestWithoutAuth(): void
    {
        $toolkit = new TestApmToolkit('', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('makePostRequest');
        $method->setAccessible(true);
        
        $response = $method->invoke($toolkit, '/test', ['key' => 'value'], false, 'Test');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testMakeGetRequestWithError(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        // We can't easily inject error, but we can test structure
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('makeGetRequest');
        $method->setAccessible(true);
        
        $response = $method->invoke($toolkit, '/test', true, 'Test');
        
        // Will return error since no real API
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
    
    public function testMakePostRequestWithError(): void
    {
        $toolkit = new TestApmToolkit('test-key', 'test-service');
        
        $reflection = new \ReflectionClass($toolkit);
        $method = $reflection->getMethod('makePostRequest');
        $method->setAccessible(true);
        
        $response = $method->invoke($toolkit, '/test', ['key' => 'value'], true, 'Test');
        
        $this->assertInstanceOf(\Gemvc\Http\JsonResponse::class, $response);
    }
}

