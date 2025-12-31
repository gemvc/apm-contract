<?php
namespace Gemvc\Core\Apm\Tests\Helpers;

use Gemvc\Core\Apm\AbstractApm;

/**
 * Test APM Provider for testing AbstractApm functionality
 */
class TestApmProvider extends AbstractApm
{
    public array $spans = [];
    public bool $flushed = false;
    
    protected function loadConfiguration(array $config = []): void
    {
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
        // For testing, we just track it
    }
    
    public function recordException(array $spanData, \Throwable $exception): array
    {
        // In real implementation, this would add exception to span
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
}

