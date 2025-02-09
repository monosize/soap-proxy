<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Http;

use Psr\Log\LoggerInterface;
use RuntimeException;

class CurlClient
{
    private \CurlHandle $handle;

    private string $url;

    private LoggerInterface $logger;

    private array $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TCP_KEEPALIVE => 1,
        CURLOPT_TCP_KEEPIDLE => 60,
        CURLOPT_TCP_KEEPINTVL => 60,
        CURLOPT_FORBID_REUSE => false,
        CURLOPT_FRESH_CONNECT => false,
    ];

    public function __construct(string $url, LoggerInterface $logger)
    {
        $this->url = $url;
        $this->logger = $logger;
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
    }

    public function setOptions(array $options): self
    {
        if (! isset($options[CURLOPT_HTTPHEADER])) {
            $options[CURLOPT_HTTPHEADER] = [];
        }
        $options[CURLOPT_HTTPHEADER][] = 'Connection: keep-alive';

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
