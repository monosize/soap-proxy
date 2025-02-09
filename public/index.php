<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MonoSize\SoapProxy\SoapProxy;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// Define paths
$projectRoot = dirname(__DIR__);
$envPath = $projectRoot;
$cacheDir = $projectRoot . '/var/cache/wsdl';
$logFile = $projectRoot . '/var/log/soap-proxy.log';

// Create logger with file rotation
$logger = new Logger('soap-proxy');
$logger->pushHandler(new RotatingFileHandler(
    $logFile,
    30, // Keep 30 days of logs
    Logger::DEBUG
));

try {
    // Create and configure SOAP proxy
    $proxy = SoapProxy::createFromEnv($logger, $cacheDir, $envPath);

    // Handle request
    $proxy->handle();
} catch (Throwable $e) {
    $logger->error('SOAP Proxy Error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain; charset=utf-8');
    }

    if (getenv('APP_ENV') === 'development') {
        echo "SOAP Proxy Error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
    } else {
        echo "SOAP Proxy Error: An internal error occurred.";
    }
}