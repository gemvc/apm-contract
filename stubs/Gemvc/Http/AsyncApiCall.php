<?php

namespace Gemvc\Http;

/**
 * PHPStan stub for AsyncApiCall class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual AsyncApiCall class exists in gemvc/library but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the properties and methods used by gemvc/apm-contracts.
 */
class AsyncApiCall
{
    /**
     * Set request timeouts
     * 
     * @param int $connectTimeout Connection timeout in seconds
     * @param int $readTimeout Read timeout in seconds
     * @return self
     */
    public function setTimeouts(int $connectTimeout, int $readTimeout): self
    {
        return $this;
    }
    
    /**
     * Add POST request to async queue
     * 
     * @param string $id Request identifier
     * @param string $url Request URL
     * @param array<string, mixed> $data Request payload
     * @param array<string, string> $headers HTTP headers
     * @return self
     */
    public function addPost(string $id, string $url, array $data, array $headers): self
    {
        return $this;
    }
    
    /**
     * Register response callback
     * 
     * @param string $id Request identifier
     * @param callable $callback Callback function(array $result, string $id): void
     * @return self
     */
    public function onResponse(string $id, callable $callback): self
    {
        return $this;
    }
    
    /**
     * Execute all queued requests asynchronously (fire-and-forget)
     * 
     * @return void
     */
    public function fireAndForget(): void
    {
    }
}

