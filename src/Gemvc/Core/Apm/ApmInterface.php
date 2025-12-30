<?php
namespace Gemvc\Core\Apm;

/**
 * APM Interface - Contract for all APM providers
 * 
 * This interface ensures compatibility across different APM solutions:
 * - TraceKit
 * - Datadog
 * - New Relic
 * - Elastic APM
 * - OpenTelemetry
 * 
 * All APM providers must implement this interface to work with GEMVC framework.
 * 
 * @package Gemvc\Core\Apm
 */
interface ApmInterface
{
    /**
     * Check if APM is enabled
     * 
     * @return bool
     */
    public function isEnabled(): bool;
    
    /**
     * Start a child span
     * 
     * @param string $operationName Operation name (e.g., 'database-query', 'http-client-call')
     * @param array<string, mixed> $attributes Optional attributes
     * @param int $kind Span kind: SPAN_KIND_SERVER (2), SPAN_KIND_CLIENT (3), or SPAN_KIND_INTERNAL (1) (default: SPAN_KIND_INTERNAL)
     * @return array<string, mixed> Span data: ['span_id' => string, 'trace_id' => string, 'start_time' => int]
     */
    public function startSpan(string $operationName, array $attributes = [], int $kind = self::SPAN_KIND_INTERNAL): array;
    
    /**
     * End a span and detach it from context
     * 
     * @param array<string, mixed> $spanData Span data returned from startSpan()
     * @param array<string, mixed> $finalAttributes Optional attributes to add before ending
     * @param string|null $status Span status: 'OK' or 'ERROR' (default: 'OK')
     * @return void
     */
    public function endSpan(array $spanData, array $finalAttributes = [], ?string $status = self::STATUS_OK): void;
    
    /**
     * Record an exception on a span
     * 
     * @param array<string, mixed> $spanData Span data (can be empty to use root span)
     * @param \Throwable $exception Exception to record
     * @return array<string, mixed> Updated span data
     */
    public function recordException(array $spanData, \Throwable $exception): array;
    
    /**
     * Check if response tracing is enabled
     * 
     * @return bool True if response data should be included in traces
     */
    public function shouldTraceResponse(): bool;
    
    /**
     * Check if database query tracing is enabled
     * 
     * @return bool True if database queries should be traced
     */
    public function shouldTraceDbQuery(): bool;
    
    /**
     * Check if request body tracing is enabled
     * 
     * @return bool True if request body should be included in traces
     */
    public function shouldTraceRequestBody(): bool;
    
    /**
     * Determine span status from HTTP status code
     * 
     * @param int $statusCode HTTP status code
     * @return string STATUS_ERROR if >= 400, otherwise STATUS_OK
     */
    public static function determineStatusFromHttpCode(int $statusCode): string;
    
    /**
     * Limit string size for tracing (to avoid huge traces)
     * 
     * @param string $value The string to limit
     * @return string Limited string
     */
    public static function limitStringForTracing(string $value): string;
    
    /**
     * Get current trace ID
     * 
     * @return string|null
     */
    public function getTraceId(): ?string;
    
    /**
     * Flush traces (send to APM service)
     * 
     * @return void
     */
    public function flush(): void;
    
    // Constants - OpenTelemetry standard
    public const SPAN_KIND_UNSPECIFIED = 0;
    public const SPAN_KIND_INTERNAL = 1;
    public const SPAN_KIND_SERVER = 2;
    public const SPAN_KIND_CLIENT = 3;
    public const SPAN_KIND_PRODUCER = 4;
    public const SPAN_KIND_CONSUMER = 5;
    
    public const STATUS_OK = 'OK';
    public const STATUS_ERROR = 'ERROR';
}

