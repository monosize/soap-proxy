<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Request;

use RuntimeException;

class SoapProxyRequest
{
    private string $targetHost;

    private string $requestUri;

    private bool $isWsdlRequest;

    private string $targetUrl;

    private string $proxyPath;

    public function __construct(string $targetHost, string $proxyPath = '/soap-proxy')
    {
        $this->targetHost = $targetHost;
        $this->proxyPath = $proxyPath;
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $this->isWsdlRequest = $this->determineWsdlRequest();
        $this->targetUrl = $this->buildTargetUrl();
    }

    private function determineWsdlRequest(): bool
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($requestMethod === 'POST') {
            return false;
        }

        return str_contains($this->requestUri, '?wsdl') ||
               str_contains($this->requestUri, '?singleWsdl');
    }

    private function buildTargetUrl(): string
    {
        $targetPath = str_replace($this->proxyPath, '', $this->requestUri);

        return rtrim($this->targetHost, '/') . $targetPath;
    }

    public function isWsdl(): bool
    {
        return $this->isWsdlRequest;
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * @throws RuntimeException
     */
    public function getCredentials(): array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            throw new RuntimeException('Authentication required');
        }

        if (preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
            $credentials = base64_decode($matches[1]);
            [$user, $pass] = explode(':', $credentials, 2);

            if (empty($user) || empty($pass)) {
                throw new RuntimeException('Invalid credentials format');
            }

            return [
                'user' => $user,
                'pass' => $pass,
            ];
        }

        throw new RuntimeException('Only Basic authentication is supported');
    }

    public function getRawPostData(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
