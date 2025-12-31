<?php

namespace Gemvc\Http;

/**
 * PHPStan stub for Response class
 * 
 * This stub class provides type information for PHPStan and can be used in tests.
 * The actual Response class exists in gemvc/library but isn't available
 * during development due to circular dependency.
 * 
 * This stub includes only the static methods used by gemvc/apm-contracts.
 */
class Response
{
    /**
     * Create success response
     * 
     * @param mixed $data Response data
     * @param int|null $count Optional count
     * @param string|null $service_message Optional service message
     * @return JsonResponse
     */
    public static function success(mixed $data, ?int $count = null, ?string $service_message = null): JsonResponse
    {
        $response = new JsonResponse();
        $response->response_code = 200;
        $response->data = $data;
        $response->service_message = $service_message;
        return $response;
    }
    
    /**
     * Create unauthorized response
     * 
     * @param string|null $service_message Optional service message
     * @return JsonResponse
     */
    public static function unauthorized(?string $service_message = null): JsonResponse
    {
        $response = new JsonResponse();
        $response->response_code = 401;
        $response->data = [];
        $response->service_message = $service_message;
        return $response;
    }
    
    /**
     * Create internal error response
     * 
     * @param string|null $service_message Optional service message
     * @return JsonResponse
     */
    public static function internalError(?string $service_message = null): JsonResponse
    {
        $response = new JsonResponse();
        $response->response_code = 500;
        $response->data = [];
        $response->service_message = $service_message;
        return $response;
    }
    
    /**
     * Create bad request response
     * 
     * @param string|null $service_message Optional service message
     * @return JsonResponse
     */
    public static function badRequest(?string $service_message = null): JsonResponse
    {
        $response = new JsonResponse();
        $response->response_code = 400;
        $response->data = [];
        $response->service_message = $service_message;
        return $response;
    }
}

