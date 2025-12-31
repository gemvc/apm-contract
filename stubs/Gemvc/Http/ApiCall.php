<?php

namespace Gemvc\Http;

/**
 * PHPStan stub for ApiCall class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual ApiCall class exists in gemvc/library but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the properties and methods used by gemvc/apm-contracts.
 */
class ApiCall
{
    /**
     * HTTP headers
     * 
     * @var array<string, string>
     */
    public array $header = [];
    
    /**
     * Error message if request failed
     * 
     * @var string|null
     */
    public ?string $error = null;
    
    /**
     * Make GET request
     * 
     * @param string $url Request URL
     * @return string|false Response body or false on failure
     */
    public function get(string $url): string|false
    {
        return '';
    }
    
    /**
     * Make POST request
     * 
     * @param string $url Request URL
     * @param array<string, mixed> $data Request payload
     * @return string|false Response body or false on failure
     */
    public function post(string $url, array $data): string|false
    {
        return '';
    }
    
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
}

