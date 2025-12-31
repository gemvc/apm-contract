<?php
namespace Gemvc\Core\Apm;

use Gemvc\Http\ApiCall;
use Gemvc\Http\AsyncApiCall;
use Gemvc\Http\JsonResponse;
use Gemvc\Http\Response;

/**
 * Abstract APM Toolkit Base Class - Shared implementation for all APM provider toolkits
 * 
 * This abstract class provides common functionality that all APM toolkits share:
 * - API key and service name management
 * - HTTP request helpers (GET, POST)
 * - JSON response parsing
 * - Error handling
 * - Async heartbeat support
 * 
 * Provider-specific toolkits must extend this class and implement
 * provider-specific API endpoints and base URL configuration.
 * 
 * @package Gemvc\Core\Apm
 */
abstract class AbstractApmToolkit implements ApmToolkitInterface
{
    /**
     * API key for authentication
     * 
     * @var string
     */
    protected string $apiKey;
    
    /**
     * Base URL for API calls
     * 
     * @var string
     */
    protected string $baseUrl;
    
    /**
     * Service name identifier
     * 
     * @var string
     */
    protected string $serviceName;
    
    /**
     * Initialize APM Toolkit
     * 
     * @param string|null $apiKey API key (optional, can be set later or loaded from env)
     * @param string|null $serviceName Service name (optional, loaded from env if not provided)
     */
    public function __construct(?string $apiKey = null, ?string $serviceName = null)
    {
        $this->apiKey = $apiKey ?? $this->getApiKeyFromEnv() ?? '';
        $this->baseUrl = $this->getBaseUrlFromEnv() ?? $this->getDefaultBaseUrl();
        $this->serviceName = $serviceName ?? $this->getServiceNameFromEnv() ?? 'gemvc-app';
    }
    
    /**
     * Set API key
     * 
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }
    
    /**
     * Set service name
     * 
     * @param string $serviceName
     * @return self
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }
    
    /**
     * Get API key from environment variable
     * 
     * Override this method to use provider-specific environment variable name.
     * Default: checks both provider-specific and unified APM_API_KEY
     * 
     * @return string|null
     */
    protected function getApiKeyFromEnv(): ?string
    {
        $providerKey = $this->getProviderApiKeyEnvName();
        if ($providerKey !== null && isset($_ENV[$providerKey]) && is_string($_ENV[$providerKey])) {
            return $_ENV[$providerKey];
        }
        
        if (isset($_ENV['APM_API_KEY']) && is_string($_ENV['APM_API_KEY'])) {
            return $_ENV['APM_API_KEY'];
        }
        
        return null;
    }
    
    /**
     * Get base URL from environment variable
     * 
     * Override this method to use provider-specific environment variable name.
     * 
     * @return string|null
     */
    protected function getBaseUrlFromEnv(): ?string
    {
        $providerKey = $this->getProviderBaseUrlEnvName();
        if ($providerKey !== null && isset($_ENV[$providerKey]) && is_string($_ENV[$providerKey])) {
            return $_ENV[$providerKey];
        }
        
        return null;
    }
    
    /**
     * Get service name from environment variable
     * 
     * Override this method to use provider-specific environment variable name.
     * 
     * @return string|null
     */
    protected function getServiceNameFromEnv(): ?string
    {
        $providerKey = $this->getProviderServiceNameEnvName();
        if ($providerKey !== null && isset($_ENV[$providerKey]) && is_string($_ENV[$providerKey])) {
            return $_ENV[$providerKey];
        }
        
        return null;
    }
    
    /**
     * Get provider-specific API key environment variable name
     * 
     * Override this in provider toolkits to return the provider-specific env var name.
     * Example: For TraceKit, return 'TRACEKIT_API_KEY'
     * 
     * @return string|null Provider-specific env var name, or null to use only APM_API_KEY
     */
    abstract protected function getProviderApiKeyEnvName(): ?string;
    
    /**
     * Get provider-specific base URL environment variable name
     * 
     * Override this in provider toolkits to return the provider-specific env var name.
     * Example: For TraceKit, return 'TRACEKIT_BASE_URL'
     * 
     * @return string|null Provider-specific env var name, or null to use default
     */
    abstract protected function getProviderBaseUrlEnvName(): ?string;
    
    /**
     * Get provider-specific service name environment variable name
     * 
     * Override this in provider toolkits to return the provider-specific env var name.
     * Example: For TraceKit, return 'TRACEKIT_SERVICE_NAME'
     * 
     * @return string|null Provider-specific env var name, or null to use default
     */
    abstract protected function getProviderServiceNameEnvName(): ?string;
    
    /**
     * Get default base URL for the provider
     * 
     * Override this in provider toolkits to return the provider's default API base URL.
     * 
     * @return string Default base URL
     */
    abstract protected function getDefaultBaseUrl(): string;
    
    // ==========================================
    // Helper Methods
    // ==========================================
    
    /**
     * Check if API key is set, return unauthorized response if not
     * 
     * @return JsonResponse|null Returns unauthorized response if API key is empty, null otherwise
     */
    protected function requireApiKey(): ?JsonResponse
    {
        if (empty($this->apiKey)) {
            return Response::unauthorized('API key not set');
        }
        return null;
    }
    
    /**
     * Create and configure ApiCall instance with common headers
     * 
     * @param bool $requireAuth Whether to include X-API-Key header (default: true)
     * @param bool $isJson Whether to include Content-Type: application/json header (default: false)
     * @return ApiCall Configured ApiCall instance
     */
    protected function createApiCall(bool $requireAuth = true, bool $isJson = false): ApiCall
    {
        $apiCall = new ApiCall();
        
        if ($requireAuth && !empty($this->apiKey)) {
            $apiCall->header['X-API-Key'] = $this->apiKey;
        }
        
        if ($isJson) {
            $apiCall->header['Content-Type'] = 'application/json';
        }
        
        return $apiCall;
    }
    
    /**
     * Parse JSON response and handle errors
     * 
     * @param string|false $response Raw response string (can be false from ApiCall)
     * @param string $errorContext Context for error messages (e.g., 'Status check')
     * @return JsonResponse Parsed response or error response
     */
    protected function parseJsonResponse(string|false $response, string $errorContext): JsonResponse
    {
        if ($response === false) {
            return Response::internalError('Invalid response from APM provider');
        }
        
        $data = json_decode($response, true);
        if (!$data || !is_array($data)) {
            return Response::internalError('Invalid response from APM provider');
        }
        return Response::success($data, 1, $errorContext . ' completed successfully');
    }
    
    /**
     * Make GET request with full error handling
     * 
     * @param string $endpoint API endpoint (relative to baseUrl)
     * @param bool $requireAuth Whether API key is required (default: true)
     * @param string $successMessage Success message for response
     * @return JsonResponse
     */
    protected function makeGetRequest(string $endpoint, bool $requireAuth = true, string $successMessage = 'Request completed successfully'): JsonResponse
    {
        if ($requireAuth) {
            $unauthorized = $this->requireApiKey();
            if ($unauthorized !== null) {
                return $unauthorized;
            }
        }
        
        try {
            $apiCall = $this->createApiCall($requireAuth, false);
            $response = $apiCall->get($this->baseUrl . $endpoint);
            
            if ($apiCall->error) {
                return Response::badRequest($successMessage . ' failed: ' . $apiCall->error);
            }
            
            return $this->parseJsonResponse($response, $successMessage);
        } catch (\Throwable $e) {
            return Response::internalError($successMessage . ' error: ' . $e->getMessage());
        }
    }
    
    /**
     * Make POST request with full error handling
     * 
     * @param string $endpoint API endpoint (relative to baseUrl)
     * @param array<string, mixed> $payload Request payload
     * @param bool $requireAuth Whether API key is required (default: true)
     * @param string $successMessage Success message for response
     * @return JsonResponse
     */
    protected function makePostRequest(string $endpoint, array $payload, bool $requireAuth = true, string $successMessage = 'Request completed successfully'): JsonResponse
    {
        if ($requireAuth) {
            $unauthorized = $this->requireApiKey();
            if ($unauthorized !== null) {
                return $unauthorized;
            }
        }
        
        try {
            $apiCall = $this->createApiCall($requireAuth, true);
            $response = $apiCall->post($this->baseUrl . $endpoint, $payload);
            
            if ($apiCall->error) {
                return Response::badRequest($successMessage . ' failed: ' . $apiCall->error);
            }
            
            return $this->parseJsonResponse($response, $successMessage);
        } catch (\Throwable $e) {
            return Response::internalError($successMessage . ' error: ' . $e->getMessage());
        }
    }
    
    // ==========================================
    // Interface Methods - Default Implementations
    // ==========================================
    
    /**
     * Register a new service in the APM provider
     * 
     * @param string $email Email address for verification
     * @param string|null $organizationName Optional organization name
     * @param string $source Partner/framework code (default: 'gemvc')
     * @param array<string, mixed> $sourceMetadata Optional metadata (version, environment, etc.)
     * @return JsonResponse
     */
    public function registerService(
        string $email,
        ?string $organizationName = null,
        string $source = 'gemvc',
        array $sourceMetadata = []
    ): JsonResponse {
        $payload = [
            'email' => $email,
            'service_name' => $this->serviceName,
            'source' => $source,
        ];
        
        if ($organizationName !== null) {
            $payload['organization_name'] = $organizationName;
        }
        
        if (!empty($sourceMetadata)) {
            $payload['source_metadata'] = $sourceMetadata;
        }
        
        $response = $this->makePostRequest($this->getRegisterEndpoint(), $payload, false, 'Registration');
        
        // Override success message for registration
        if ($response->response_code === 200) {
            return Response::success($response->data, 1, 'Verification code sent to email');
        }
        
        return $response;
    }
    
    /**
     * Verify email code and get API key
     * 
     * @param string $sessionId Session ID from registerService()
     * @param string $code Verification code from email
     * @return JsonResponse
     */
    public function verifyCode(string $sessionId, string $code): JsonResponse
    {
        $payload = [
            'session_id' => $sessionId,
            'code' => $code,
        ];
        
        $response = $this->makePostRequest($this->getVerifyEndpoint(), $payload, false, 'Verification');
        
        // Update API key if provided
        if ($response->response_code === 200 && is_array($response->data)) {
            if (isset($response->data['api_key']) && is_string($response->data['api_key'])) {
                $this->apiKey = $response->data['api_key'];
            }
            return Response::success($response->data, 1, 'Service registered successfully');
        }
        
        return $response;
    }
    
    /**
     * Check integration status
     * 
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        return $this->makeGetRequest($this->getStatusEndpoint(), true, 'Status check');
    }
    
    /**
     * Send heartbeat to APM provider (synchronous)
     * 
     * @param string $status Service status: 'healthy', 'degraded', 'unhealthy'
     * @param array<string, mixed> $metadata Optional metadata (memory_usage, cpu_usage, etc.)
     * @return JsonResponse
     */
    public function sendHeartbeat(string $status = 'healthy', array $metadata = []): JsonResponse
    {
        $unauthorized = $this->requireApiKey();
        if ($unauthorized !== null) {
            return $unauthorized;
        }
        
        try {
            $apiCall = $this->createApiCall(true, true);
            $apiCall->setTimeouts(1, 3); // Short timeouts for heartbeats
            
            $payload = [
                'service_name' => $this->serviceName,
                'status' => $status,
            ];
            
            if (!empty($metadata)) {
                $payload['metadata'] = $metadata;
            }
            
            $response = $apiCall->post($this->baseUrl . $this->getHeartbeatEndpoint(), $payload);
            
            if ($apiCall->error) {
                return Response::badRequest('Heartbeat failed: ' . $apiCall->error);
            }
            
            return $this->parseJsonResponse($response, 'Heartbeat');
        } catch (\Throwable $e) {
            return Response::internalError('Heartbeat error: ' . $e->getMessage());
        }
    }
    
    /**
     * Send heartbeat asynchronously (non-blocking)
     * 
     * Uses AsyncApiCall with fire-and-forget mode for reliable non-blocking execution.
     * 
     * @param string $status Service status
     * @param array<string, mixed> $metadata Optional metadata
     * @return void
     */
    public function sendHeartbeatAsync(string $status = 'healthy', array $metadata = []): void
    {
        if (empty($this->apiKey)) {
            // Silently fail if no API key - don't log errors for missing config
            return;
        }
        
        try {
            $async = new AsyncApiCall();
            $async->setTimeouts(1, 3); // Short timeouts for heartbeats
            
            $payload = [
                'service_name' => $this->serviceName,
                'status' => $status,
            ];
            
            if (!empty($metadata)) {
                $payload['metadata'] = $metadata;
            }
            
            $headers = [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];
            
            $async->addPost('heartbeat', $this->baseUrl . $this->getHeartbeatEndpoint(), $payload, $headers)
                  ->onResponse('heartbeat', function($result, $id) {
                      if (!$result['success']) {
                          error_log("APM Toolkit: Heartbeat failed: " . ($result['error'] ?? 'Unknown error'));
                      }
                  })
                  ->fireAndForget();
        } catch (\Throwable $e) {
            // Silently fail - don't let heartbeat errors break the application
            error_log("APM Toolkit: Heartbeat error: " . $e->getMessage());
        }
    }
    
    /**
     * List health checks
     * 
     * @return JsonResponse
     */
    public function listHealthChecks(): JsonResponse
    {
        return $this->makeGetRequest($this->getHealthChecksEndpoint(), true, 'Health checks');
    }
    
    /**
     * Get service metrics
     * 
     * @param string $window Time window: '5m', '15m', '1h', '6h', '24h' (default: '15m')
     * @return JsonResponse
     */
    public function getMetrics(string $window = '15m'): JsonResponse
    {
        // Replace {serviceName} placeholder if present in endpoint
        $endpoint = str_replace('{serviceName}', urlencode($this->serviceName), $this->getMetricsEndpoint());
        $endpoint .= '?window=' . urlencode($window);
        return $this->makeGetRequest($endpoint, true, 'Metrics');
    }
    
    /**
     * Get alerts summary
     * 
     * @return JsonResponse
     */
    public function getAlertsSummary(): JsonResponse
    {
        return $this->makeGetRequest($this->getAlertsSummaryEndpoint(), true, 'Alerts summary');
    }
    
    /**
     * Get active alerts
     * 
     * @param int $limit Maximum number of alerts to return (default: 50)
     * @return JsonResponse
     */
    public function getActiveAlerts(int $limit = 50): JsonResponse
    {
        $endpoint = $this->getActiveAlertsEndpoint() . '?limit=' . $limit;
        return $this->makeGetRequest($endpoint, true, 'Active alerts');
    }
    
    /**
     * Create a webhook
     * 
     * @param string $name Webhook name
     * @param string $url Webhook URL
     * @param array<string> $events Event types to subscribe to
     * @param bool $enabled Whether webhook is enabled (default: true)
     * @return JsonResponse
     */
    public function createWebhook(
        string $name,
        string $url,
        array $events,
        bool $enabled = true
    ): JsonResponse {
        $payload = [
            'name' => $name,
            'url' => $url,
            'events' => $events,
            'enabled' => $enabled,
        ];
        
        return $this->makePostRequest($this->getWebhooksEndpoint(), $payload, true, 'Webhook creation');
    }
    
    /**
     * List webhooks
     * 
     * @return JsonResponse
     */
    public function listWebhooks(): JsonResponse
    {
        return $this->makeGetRequest($this->getWebhooksEndpoint(), true, 'Webhooks');
    }
    
    /**
     * Get current subscription info
     * 
     * @return JsonResponse
     */
    public function getSubscription(): JsonResponse
    {
        return $this->makeGetRequest($this->getSubscriptionEndpoint(), true, 'Subscription');
    }
    
    /**
     * List available plans
     * 
     * @return JsonResponse
     */
    public function listPlans(): JsonResponse
    {
        return $this->makeGetRequest($this->getPlansEndpoint(), false, 'Plans');
    }
    
    /**
     * Create checkout session for plan upgrade
     * 
     * @param string $planId Plan ID (e.g., 'starter', 'pro')
     * @param string $billingInterval 'monthly' or 'yearly'
     * @param string $source Source identifier (default: 'gemvc')
     * @param string|null $successUrl Optional success redirect URL
     * @param string|null $cancelUrl Optional cancel redirect URL
     * @return JsonResponse
     */
    public function createCheckoutSession(
        string $planId,
        string $billingInterval = 'monthly',
        string $source = 'gemvc',
        ?string $successUrl = null,
        ?string $cancelUrl = null
    ): JsonResponse {
        $payload = [
            'plan_id' => $planId,
            'billing_interval' => $billingInterval,
            'source' => $source,
        ];
        
        if ($successUrl !== null) {
            $payload['success_url'] = $successUrl;
        }
        
        if ($cancelUrl !== null) {
            $payload['cancel_url'] = $cancelUrl;
        }
        
        return $this->makePostRequest($this->getCheckoutSessionEndpoint(), $payload, true, 'Checkout session creation');
    }
    
    // ==========================================
    // Abstract Methods - Provider-Specific Endpoints
    // ==========================================
    
    /**
     * Get registration endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/integrate/register')
     */
    abstract protected function getRegisterEndpoint(): string;
    
    /**
     * Get verification endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/integrate/verify')
     */
    abstract protected function getVerifyEndpoint(): string;
    
    /**
     * Get status endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/integrate/status')
     */
    abstract protected function getStatusEndpoint(): string;
    
    /**
     * Get heartbeat endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/health/heartbeat')
     */
    abstract protected function getHeartbeatEndpoint(): string;
    
    /**
     * Get health checks endpoint
     * 
     * @return string Endpoint path (e.g., '/api/health-checks')
     */
    abstract protected function getHealthChecksEndpoint(): string;
    
    /**
     * Get metrics endpoint
     * 
     * @return string Endpoint path (e.g., '/api/metrics/services/{serviceName}')
     */
    abstract protected function getMetricsEndpoint(): string;
    
    /**
     * Get alerts summary endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/alerts/summary')
     */
    abstract protected function getAlertsSummaryEndpoint(): string;
    
    /**
     * Get active alerts endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/alerts/active')
     */
    abstract protected function getActiveAlertsEndpoint(): string;
    
    /**
     * Get webhooks endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/webhooks')
     */
    abstract protected function getWebhooksEndpoint(): string;
    
    /**
     * Get subscription endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/billing/subscription')
     */
    abstract protected function getSubscriptionEndpoint(): string;
    
    /**
     * Get plans endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/billing/plans')
     */
    abstract protected function getPlansEndpoint(): string;
    
    /**
     * Get checkout session endpoint
     * 
     * @return string Endpoint path (e.g., '/v1/billing/create-checkout-session')
     */
    abstract protected function getCheckoutSessionEndpoint(): string;
}

