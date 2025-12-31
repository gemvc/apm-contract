<?php
namespace Gemvc\Core\Apm\Tests\Helpers;

use Gemvc\Http\Request;

/**
 * Mock Request class for testing
 * 
 * TODO: Replace with actual Request class once gemvc/library is updated
 * This mock implements the methods needed by AbstractApm
 */
class MockRequest extends Request
{
    public ?object $apm = null;
    
    public array $post = [];
    public ?array $put = null;
    public ?array $patch = null;
    
    private string $method = 'GET';
    private string $uri = '/';
    private array $headers = [];
    private string $serviceName = 'test';
    private string $methodName = 'index';
    
    public function __construct(
        string $method = 'GET',
        string $uri = '/',
        array $headers = [],
        array $body = []
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        
        // Set body data based on method
        if ($method === 'POST') {
            $this->post = $body;
        } elseif ($method === 'PUT') {
            $this->put = $body;
        } elseif ($method === 'PATCH') {
            $this->patch = $body;
        }
    }
    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    public function getUri(): string
    {
        return $this->uri;
    }
    
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }
        return null;
    }
    
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    public function getMethodName(): string
    {
        return $this->methodName;
    }
    
    public function setServiceName(string $name): void
    {
        $this->serviceName = $name;
    }
    
    public function setMethodName(string $name): void
    {
        $this->methodName = $name;
    }
}

