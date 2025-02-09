<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy;

use MonoSize\SoapProxy\Config\Environment;
use MonoSize\SoapProxy\Handler\SoapHandler;
use MonoSize\SoapProxy\Handler\WsdlHandler;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SoapProxy
{
    private LoggerInterface $logger;

    private string $transferHost;

    private bool $debug;

    public function __construct(
        LoggerInterface $logger,
        string $transferHost,
        bool $debug = false
    ) {
        $this->logger = $logger;
        $this->transferHost = $transferHost;
        $this->debug = $debug;
    }

    /**
     * Handle incoming SOAP request
     */
    public function handle(): void
    {
        try {
            $request = new SoapProxyRequest($this->transferHost);

            if ($request->isWsdl()) {
                (new WsdlHandler($request, $this->logger))->handle();
            } else {
                (new SoapHandler($request, $this->logger))->handle();
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    /**
     * Handle errors and return appropriate response
     */
    private function handleError(\Throwable $e): void
    {
        $this->logger->error('SOAP Proxy Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (! headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
        }

        if ($this->debug) {
            echo "SOAP Proxy Error: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString();
        } else {
            echo "SOAP Proxy Error: An internal error occurred.";
        }
    }

    /**
     * Create instance from environment configuration
     */
    public static function createFromEnv(LoggerInterface $logger): self
    {
        $env = new Environment();
        $transferHost = $env->get('TRANSFERHOST');

        if (empty($transferHost)) {
            throw new RuntimeException('TRANSFERHOST environment variable must be set');
        }

        return new self(
            $logger,
            $transferHost,
            $env->get('PROXYDEBUG', '0') === '1'
        );
    }
}
