# SOAP Proxy Library

A PHP library for securely proxying SOAP requests with authentication and WSDL caching.

## Quickstart

```bash
composer require monosize/soap-proxy
```

Simple usage:

```php
use MonoSize\SoapProxy\SoapProxy;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot;
$cacheDir = $projectRoot . '/var/cache/wsdl';
$logFile = $projectRoot . '/var/log/soap-proxy.log';

// Create logger with file rotation
$logger = new Logger('soap-proxy');
$logger->pushHandler(new RotatingFileHandler(
    $logFile,
    30
));

// Create a proxy from environment variables
// Log level will be automatically set based on PROXYDEBUG environment variable
$proxy = SoapProxy::createFromEnv($logger, $cacheDir, $envPath);

// Process request
$proxy->handle();
```

## Features

- Authenticated SOAP request proxying
- WSDL caching for better performance
- Connection pooling for stable connections
- PSR-3 compliant logging with automatic debug mode control
- Comprehensive error handling
- Support for SOAP 1.1 and 1.2

## System Requirements

- PHP 8.2 or higher
- Extensions: curl, dom, xml
- Composer

## Further Documentation

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Usage](usage.md)
- [Troubleshooting](troubleshooting.md)
- [Development](development.md)

## Support

For issues or questions, please use the [GitHub Issue Tracker](https://github.com/monosize/soap-proxy/issues).

## License

This library is licensed under the MIT License. See the [LICENSE](../LICENSE) file for more details.