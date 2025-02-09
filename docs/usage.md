# Usage

## Basic Usage

### Simple Implementation

```php
<?php
// public/soap-proxy.php

require_once __DIR__ . '/../vendor/autoload.php';

use MonoSize\SoapProxy\SoapProxy;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// Create logger
$logger = new Logger('soap-proxy');
$logger->pushHandler(new RotatingFileHandler(
    '../logs/soap-proxy.log',
    30  // Keep 30 days of logs
));

try {
    // Create proxy from environment variables
    // Log level will be automatically set based on PROXYDEBUG environment variable
    $proxy = SoapProxy::createFromEnv($logger);

    // Process request
    $proxy->handle();
} catch (Throwable $e) {
    $logger->error('SOAP Error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    if (getenv('APP_ENV') === 'development') {
        throw $e;
    } else {
        http_response_code(500);
        echo "An error occurred.";
    }
}
```

### SOAP Client Example

```php
// Client code
$client = new SoapClient('http://your-proxy/soap-proxy/service?wsdl', [
    'login' => 'username',
    'password' => 'password',
    'trace' => true,
    'exceptions' => true
]);

// Make SOAP call
try {
    $result = $client->methodName([
        'parameter1' => 'value1',
        'parameter2' => 'value2'
    ]);
} catch (SoapFault $e) {
    // Error handling
    echo "SOAP Error: " . $e->getMessage();
}
```

## Advanced Usage

### With Custom Logger

```php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

// Logger with JSON formatting and file rotation
$logger = new Logger('soap-proxy');

$handler = new RotatingFileHandler(
    '../logs/soap-proxy.log',
    30  // Keep for 30 days
);
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

// Create proxy from environment variables
// Debug mode will be controlled by PROXYDEBUG environment variable
$proxy = SoapProxy::createFromEnv($logger);
```

### Error Handling

```php
try {
    $proxy->handle();
} catch (RuntimeException $e) {
    switch ($e->getCode()) {
        case 401:
            // Authentication error
            $logger->error('Authentication failed', [
                'message' => $e->getMessage()
            ]);
            http_response_code(401);
            echo "Authentication required";
            break;

        case 404:
            // Service not found
            $logger->error('Service not found', [
                'message' => $e->getMessage()
            ]);
            http_response_code(404);
            echo "Service not found";
            break;

        default:
            // General error
            $logger->error('Unexpected error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            http_response_code(500);
            echo "An error occurred";
    }
}
```

### WSDL Caching

```php
use MonoSize\SoapProxy\Cache\WsdlCache;

// Configure cache directory and TTL
$cacheDir = '/path/to/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Initialize cache
$cache = new WsdlCache($cacheDir, $logger);

// Use cache with proxy
$proxy = new SoapProxy(
    $logger,
    'https://target-soap-server.com',
    false,
    $cache
);
```

## Practical Examples

### SOAP Request Debug Logging

```php
// Enable debug logging for SOAP requests
$client = new SoapClient('http://your-proxy/soap-proxy/service?wsdl', [
    'login' => 'username',
    'password' => 'password',
    'trace' => true
]);

try {
    $result = $client->methodName(['param' => 'value']);

    // Log request and response
    echo "Request Headers:\n" . $client->__getLastRequestHeaders();
    echo "Request:\n" . $client->__getLastRequest();
    echo "Response Headers:\n" . $client->__getLastResponseHeaders();
    echo "Response:\n" . $client->__getLastResponse();
} catch (SoapFault $e) {
    echo "Error: " . $e->getMessage();
}
```

### Multiple Services

```php
// Different services through the same proxy
$serviceUrls = [
    'service1' => 'http://your-proxy/soap-proxy/service1',
    'service2' => 'http://your-proxy/soap-proxy/service2'
];

foreach ($serviceUrls as $name => $url) {
    $client = new SoapClient($url . '?wsdl', [
        'login' => 'username',
        'password' => 'password'
    ]);

    try {
        $result = $client->testMethod();
        echo "$name: OK\n";
    } catch (SoapFault $e) {
        echo "$name: Error - " . $e->getMessage() . "\n";
    }
}
```
