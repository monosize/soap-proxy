# SOAP Proxy

A secure PHP-based SOAP proxy that authenticates SOAP requests and forwards them to a target server. Supports both WSDL queries and SOAP messages.

## Features

- Proxying SOAP requests with authentication
- Support for SOAP 1.1 and 1.2
- WSDL caching and forwarding
- Configurable target servers
- Automatic debug logging control via PROXYDEBUG
- Flexible error handling
- Basic authentication support
- Connection pooling for improved performance

## Installation

```bash
composer require monosize/soap-proxy
```

## Quick Start

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

## Documentation

The full documentation can be found in the [docs](docs) directory:

- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Usage](docs/usage.md)
- [Troubleshooting](docs/troubleshooting.md)

## System Requirements

- PHP 8.2 or higher
- Extensions: curl, dom, xml
- Composer

## Support

If you encounter issues or have questions, please create an issue in the [GitHub Issue Tracker](https://github.com/monosize/soap-proxy/issues).

## Contribution

Contributions are welcome!

## License

This library is licensed under the MIT license. For more details, see the [LICENSE](LICENSE) file.

## Credits

Created by Frank Rakow <frank.rakow@gestalten.de>
