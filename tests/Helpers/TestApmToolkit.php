<?php
namespace Gemvc\Core\Apm\Tests\Helpers;

use Gemvc\Core\Apm\AbstractApmToolkit;

/**
 * Test APM Toolkit for testing AbstractApmToolkit functionality
 */
class TestApmToolkit extends AbstractApmToolkit
{
    protected function getProviderApiKeyEnvName(): ?string
    {
        return 'TEST_APM_API_KEY';
    }
    
    protected function getProviderBaseUrlEnvName(): ?string
    {
        return 'TEST_APM_BASE_URL';
    }
    
    protected function getProviderServiceNameEnvName(): ?string
    {
        return 'TEST_APM_SERVICE_NAME';
    }
    
    protected function getDefaultBaseUrl(): string
    {
        return 'https://api.test-apm.com';
    }
    
    protected function getRegisterEndpoint(): string
    {
        return '/v1/integrate/register';
    }
    
    protected function getVerifyEndpoint(): string
    {
        return '/v1/integrate/verify';
    }
    
    protected function getStatusEndpoint(): string
    {
        return '/v1/integrate/status';
    }
    
    protected function getHeartbeatEndpoint(): string
    {
        return '/v1/health/heartbeat';
    }
    
    protected function getHealthChecksEndpoint(): string
    {
        return '/api/health-checks';
    }
    
    protected function getMetricsEndpoint(): string
    {
        return '/api/metrics/services/{serviceName}';
    }
    
    protected function getAlertsSummaryEndpoint(): string
    {
        return '/v1/alerts/summary';
    }
    
    protected function getActiveAlertsEndpoint(): string
    {
        return '/v1/alerts/active';
    }
    
    protected function getWebhooksEndpoint(): string
    {
        return '/v1/webhooks';
    }
    
    protected function getSubscriptionEndpoint(): string
    {
        return '/v1/billing/subscription';
    }
    
    protected function getPlansEndpoint(): string
    {
        return '/v1/billing/plans';
    }
    
    protected function getCheckoutSessionEndpoint(): string
    {
        return '/v1/billing/create-checkout-session';
    }
}

