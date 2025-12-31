<?php
namespace Gemvc\Core\Apm\Tests\Helpers;

use Gemvc\Core\Apm\AbstractApm;

/**
 * Test APM Provider that throws exception in loadConfiguration for testing error handling
 */
class TestApmProviderWithException extends AbstractApm
{
    public array $spans = [];
    public bool $flushed = false;
    private bool $shouldThrowException = false;
    
    public function __construct($request = null, array $config = [])
    {
        // Don't throw exception in constructor, only when init() is called
        parent::__construct($request, $config);
    }
    
    public function setShouldThrowException(bool $shouldThrow): void
    {
        $this->shouldThrowException = $shouldThrow;
    }
    
    protected function loadConfiguration(array $config = []): void
    {
        if ($this->shouldThrowException) {
            throw new \RuntimeException('Test exception in loadConfiguration');
        }
        
        $this->enabled = $config['enabled'] ?? true;
        $this->sampleRate = $config['sample_rate'] ?? 1.0;
        $this->traceResponse = $config['trace_response'] ?? false;
        $this->traceDbQuery = $config['trace_db_query'] ?? false;
        $this->traceRequestBody = $config['trace_request_body'] ?? false;
    }
    
    protected function initializeRootTrace(): void
    {
        if ($this->request === null) {
            return;
        }
        
        $this->rootSpan = $this->startSpan('http-request', [
            'http.method' => $this->request->getMethod(),
            'http.url' => $this->request->getUri(),
        ]);
        
        $this->traceId = $this->rootSpan['trace_id'] ?? null;
    }
    
    public function startSpan(string $operationName, array $attributes = [], int $kind = self::SPAN_KIND_INTERNAL): array
    {
        if (!$this->isEnabled()) {
            return [];
        }
        
        $span = [
            'span_id' => uniqid('span_', true),
            'trace_id' => $this->traceId ?? uniqid('trace_', true),
            'start_time' => (int)(microtime(true) * 1000000),
            'operation_name' => $operationName,
            'attributes' => $attributes,
            'kind' => $kind,
        ];
        
        $this->spans[] = $span;
        
        if ($this->traceId === null) {
            $this->traceId = $span['trace_id'];
        }
        
        return $span;
    }
    
    public function endSpan(array $spanData, array $finalAttributes = [], ?string $status = self::STATUS_OK): void
    {
        // In real implementation, this would update the span
    }
    
    public function recordException(array $spanData, \Throwable $exception): array
    {
        return $spanData;
    }
    
    public function flush(): void
    {
        $this->flushed = true;
    }
    
    public function getTraceId(): ?string
    {
        return $this->traceId;
    }
    
    public function init(array $config = []): bool
    {
        // Set flag to throw exception when loadConfiguration is called
        $this->shouldThrowException = true;
        
        // Call parent init() which will call loadConfiguration() and throw exception
        return parent::init($config);
    }
}

