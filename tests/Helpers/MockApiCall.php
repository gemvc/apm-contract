<?php
namespace Gemvc\Core\Apm\Tests\Helpers;

use Gemvc\Http\ApiCall;

/**
 * Mock ApiCall for testing
 */
class MockApiCall extends ApiCall
{
    public string|false $mockResponse = '';
    public ?string $mockError = null;
    public array $capturedUrl = [];
    public array $capturedPayload = [];
    
    public function get(string $url): string|false
    {
        $this->capturedUrl[] = $url;
        if ($this->mockError !== null) {
            $this->error = $this->mockError;
            return false;
        }
        $this->error = null;
        return $this->mockResponse;
    }
    
    public function post(string $url, array $data): string|false
    {
        $this->capturedUrl[] = $url;
        $this->capturedPayload[] = $data;
        if ($this->mockError !== null) {
            $this->error = $this->mockError;
            return false;
        }
        $this->error = null;
        return $this->mockResponse;
    }
    
    public function postRaw(string $remoteApiUrl, string $rawBody, string $contentType): string|false
    {
        $this->capturedUrl[] = $remoteApiUrl;
        $this->capturedPayload[] = $rawBody; // Store raw body as string
        if ($this->mockError !== null) {
            $this->error = $this->mockError;
            return false;
        }
        $this->error = null;
        return $this->mockResponse;
    }
}

