<?php
namespace Gemvc\Core\Apm;

use Gemvc\Http\JsonResponse;

/**
 * APM Toolkit Interface - Contract for all APM provider toolkits
 * 
 * Toolkits provide client-side integration and management features for APM providers:
 * - Account registration and email verification
 * - Health check monitoring (heartbeats)
 * - Service status and metrics
 * - Alert management
 * - Webhook management
 * - Subscription & billing information
 * 
 * All APM provider toolkits must implement this interface to work with GEMVC framework.
 * 
 * @package Gemvc\Core\Apm
 */
interface ApmToolkitInterface
{
    /**
     * Set API key for authentication
     * 
     * @param string $apiKey API key
     * @return self
     */
    public function setApiKey(string $apiKey): self;
    
    /**
     * Set service name identifier
     * 
     * @param string $serviceName Service name
     * @return self
     */
    public function setServiceName(string $serviceName): self;
    
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
    ): JsonResponse;
    
    /**
     * Verify email code and get API key
     * 
     * @param string $sessionId Session ID from registerService()
     * @param string $code Verification code from email
     * @return JsonResponse
     */
    public function verifyCode(string $sessionId, string $code): JsonResponse;
    
    /**
     * Check integration status
     * 
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse;
    
    /**
     * Send heartbeat to APM provider (synchronous)
     * 
     * @param string $status Service status: 'healthy', 'degraded', 'unhealthy'
     * @param array<string, mixed> $metadata Optional metadata (memory_usage, cpu_usage, etc.)
     * @return JsonResponse
     */
    public function sendHeartbeat(string $status = 'healthy', array $metadata = []): JsonResponse;
    
    /**
     * Send heartbeat asynchronously (non-blocking)
     * 
     * Uses fire-and-forget mode for reliable non-blocking execution.
     * 
     * @param string $status Service status
     * @param array<string, mixed> $metadata Optional metadata
     * @return void
     */
    public function sendHeartbeatAsync(string $status = 'healthy', array $metadata = []): void;
    
    /**
     * List health checks
     * 
     * @return JsonResponse
     */
    public function listHealthChecks(): JsonResponse;
    
    /**
     * Get service metrics
     * 
     * @param string $window Time window: '5m', '15m', '1h', '6h', '24h' (default: '15m')
     * @return JsonResponse
     */
    public function getMetrics(string $window = '15m'): JsonResponse;
    
    /**
     * Get alerts summary
     * 
     * @return JsonResponse
     */
    public function getAlertsSummary(): JsonResponse;
    
    /**
     * Get active alerts
     * 
     * @param int $limit Maximum number of alerts to return (default: 50)
     * @return JsonResponse
     */
    public function getActiveAlerts(int $limit = 50): JsonResponse;
    
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
    ): JsonResponse;
    
    /**
     * List webhooks
     * 
     * @return JsonResponse
     */
    public function listWebhooks(): JsonResponse;
    
    /**
     * Get current subscription info
     * 
     * @return JsonResponse
     */
    public function getSubscription(): JsonResponse;
    
    /**
     * List available plans
     * 
     * @return JsonResponse
     */
    public function listPlans(): JsonResponse;
    
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
    ): JsonResponse;
}

