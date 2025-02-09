<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy;

use Monolog\Level;
use MonoSize\SoapProxy\Cache\WsdlCache;
use MonoSize\SoapProxy\Config\Environment;
use MonoSize\SoapProxy\Handler\SoapHandler;
use MonoSize\SoapProxy\Handler\WsdlHandler;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SoapProxy
{
    private LoggerInterface $logger;

    private string $targetHost;

    private bool $debug;

    private WsdlCache $cache;

    public function __construct(
        LoggerInterface $logger,
        string $targetHost,
        WsdlCache $cache,
        bool $debug = false
    ) {
        $this->logger = $logger;
        $this->targetHost = $targetHost;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    public function handle(): void
    {
        try {
            $request = new SoapProxyRequest($this->targetHost);

            if ($request->isWsdl()) {
                $handler = new WsdlHandler($request, $this->logger, $this->cache);
                $handler->handle();
            } else {
                $handler = new SoapHandler($request, $this->logger);
                $handler->handle();
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

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

    public static function createFromEnv(LoggerInterface $logger, string $cacheDir, ?string $envPath = null): self
    {
        $env = new Environment($envPath);
        $targetHost = $env->get('TRANSFERHOST');

        if (empty($targetHost)) {
            throw new RuntimeException('TRANSFERHOST environment variable must be set');
        }

        $debugMode = $env->get('PROXYDEBUG', '0') === '1';
        // Set log level based on PROXYDEBUG
        if ($logger instanceof \Monolog\Logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof \Monolog\Handler\AbstractProcessingHandler) {
                    $handler->setLevel($debugMode ? Level::Debug : Level::Info);
                }
            }
        }

        $cache = new WsdlCache($cacheDir, $logger);

        return new self(
            $logger,
            $targetHost,
            $cache,
            $debugMode
        );
    }
}
