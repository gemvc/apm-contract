<?php

namespace Gemvc\Http;

/**
 * PHPStan stub for JsonResponse class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual JsonResponse class exists in gemvc/library but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the properties and methods used by gemvc/apm-contracts.
 */
class JsonResponse
{
    /**
     * HTTP response code
     * 
     * @var int
     */
    public int $response_code = 200;
    
    /**
     * Response data
     * 
     * @var array<string, mixed>|mixed
     */
    public mixed $data = [];
    
    /**
     * Service message
     * 
     * @var string|null
     */
    public ?string $service_message = null;
}

