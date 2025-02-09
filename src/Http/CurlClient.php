<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Http;

use MonoSize\SoapProxy\Config\Environment;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CurlClient
{
    private \CurlHandle $handle;

    private string $url;

    private LoggerInterface $logger;

    private Environment $environment;

    private array $defaultOptions;

    public function __construct(string $url, LoggerInterface $logger, Environment $environment)
    {
        $this->url = $url;
        $this->logger = $logger;
        $this->environment = $environment;

        // Initialize default options with environment-based SSL verification
        $this->defaultOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => filter_var($this->environment->get('SSL_VERIFY_PEER', 'false'), FILTER_VALIDATE_BOOLEAN),
            CURLOPT_SSL_VERIFYHOST => filter_var($this->environment->get('SSL_VERIFY_HOST', 'false'), FILTER_VALIDATE_BOOLEAN) ? 2 : 0,
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 60,
            CURLOPT_TCP_KEEPINTVL => 60,
            CURLOPT_FORBID_REUSE => false,
            CURLOPT_FRESH_CONNECT => false,
        ];

        $host = parse_url($url, PHP_URL_HOST);

        // Try to reuse existing connection
        $handle = CurlConnectionPool::getConnection($host);

        if ($handle) {
            $this->handle = $handle;
            curl_setopt($this->handle, CURLOPT_URL, $url);
        } else {
            $this->handle = curl_init($url);
            curl_setopt_array($this->handle, $this->defaultOptions);
        }

        $this->logger->debug('Initialized cURL client', [
            'url' => $url,
            'ssl_verify_peer' => $this->defaultOptions[CURLOPT_SSL_VERIFYPEER],
            'ssl_verify_host' => $this->defaultOptions[CURLOPT_SSL_VERIFYHOST],
        ]);
    }

    public function setOptions(array $options): self
    {
        if (! isset($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = [];
        }
        $options[CURLOPT_HTTPHEADER][] = 'Connection: keep-alive';

        // Ensure SSL verification settings from environment are not overridden
        unset($options[CURLOPT_SSL_VERIFYPEER]);
        unset($options[CURLOPT_SSL_VERIFYHOST]);

        // Set options directly without modification
        curl_setopt_array($this->handle, $options);

        $this->logger->debug('Setting cURL options', [
            'url' => $this->url,
            'headers' => $options[CURLOPT_HTTPHEADER],
        ]);

        return $this;
    }

    public function execute(): string
    {
        $response = curl_exec($this->handle);
        $httpCode = $this->getHttpCode();

        if ($response === false) {
            $error = curl_error($this->handle);
            $this->logger->error('cURL Error', [
                'error' => $error,
                'curlInfo' => curl_getinfo($this->handle),
            ]);

            throw new RuntimeException('cURL Error: ' . $error);
        }

        // We know $response is string at this point since false case is handled above
        assert(is_string($response));

        // Store successful connection in pool
        $host = parse_url($this->url, PHP_URL_HOST);
        CurlConnectionPool::storeConnection($host, $this->handle);

        return $response;
    }

    public function getHttpCode(): int
    {
        return (int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
    }

    public function close(): void
    {
        // Don't close directly as connection remains in pool
    }
}
