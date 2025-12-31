<?php

namespace Gemvc\Http;

/**
 * PHPStan stub for Request class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual Request class exists in gemvc/library 5.2.2+ but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the properties and methods used by gemvc/apm-contracts.
 */
class Request
{
    /**
     * APM instance (set by ApiService/ApmFactory for sharing with Controller and other layers)
     * 
     * @var \Gemvc\Core\Apm\ApmInterface|null
     */
    /** @phpstan-ignore-next-line - ApmInterface is defined in src/ and resolved during main analysis */
    public ?object $apm = null;
    
    /**
     * POST body data
     * 
     * @var array<mixed>
     */
    public array $post = [];
    
    /**
     * PUT body data
     * 
     * @var array<mixed>|null
     */
    public ?array $put = null;
    
    /**
     * PATCH body data
     * 
     * @var array<mixed>|null
     */
    public ?array $patch = null;
    
    /**
     * Get the HTTP request method (PSR-7 compliant)
     * 
     * @return string HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string
    {
        return 'GET';
    }
    
    /**
     * Get the request URI (PSR-7 compliant)
     * 
     * @return string The request URI
     */
    public function getUri(): string
    {
        return '/';
    }
    
    /**
     * Get a specific HTTP header value (PSR-7 compliant)
     * 
     * @param string $name Header name (case-insensitive)
     * @return string|null Header value or null if not found
     */
    public function getHeader(string $name): ?string
    {
        return null;
    }
    
    /**
     * Get the service name determined during routing
     * 
     * @return string The service name or 'Index' if not set (default service)
     */
    public function getServiceName(): string
    {
        return 'Index';
    }
    
    /**
     * Get the method name determined during routing
     * 
     * @return string The method name or 'index' if not set
     */
    public function getMethodName(): string
    {
        return 'index';
    }
}

