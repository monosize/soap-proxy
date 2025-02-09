# Development

## Setting Up the Development Environment

### 1. Clone the Repository

```bash
git clone https://github.com/monosize/soap-proxy.git
cd soap-proxy
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure the Development Environment

```bash
# Create .env.development
cp .env.example .env.development

# Adjust development settings
PROXYDEBUG=1             # Enables debug logging
SSL_VERIFY_PEER=false
SSL_VERIFY_HOST=false
```

## Running Tests

### PHPUnit Tests

```bash
# Run all tests
composer test

# Specific test group
./vendor/bin/phpunit --testsuite unit

# With coverage report
./vendor/bin/phpunit --coverage-html coverage
```

### Check Code Style

```bash
# Run PHP-CS-Fixer
composer cs-fix

# Check code style without changes
composer cs-check
```

## Extending the Library

### Custom Cache Implementation

```php
namespace MonoSize\SoapProxy\Cache;

use Psr\Log\LoggerInterface;

class RedisWsdlCache extends WsdlCache
{
    private $redis;

    public function __construct(
        LoggerInterface $logger,
        \Redis $redis
    ) {
        parent::__construct($logger);
        $this->redis = $redis;
    }

    public function getCached(string $url): ?string
    {
        $key = $this->getCacheKey($url);
        return $this->redis->get($key) ?: null;
    }

    public function store(string $url, string $content): void
    {
        $key = $this->getCacheKey($url);
        $this->redis->setex($key, self::CACHE_TTL, $content);
    }

    private function getCacheKey(string $url): string
    {
        return 'wsdl:' . md5($url);
    }
}
```

### Custom Logger Implementation

```php
namespace MonoSize\SoapProxy\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class CustomLogger extends AbstractLogger
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // Skip debug messages unless debug mode is enabled
        if (!$this->debug && $level === LogLevel::DEBUG) {
            return;
        }

        // Implement your custom logging logic here
    }
}```

## Debugging

### Configure XDebug

php.ini:
```ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
```

### SOAP Debugging

```php
// Enable SOAP debug mode
$client = new \SoapClient($wsdlUrl, [
    'trace' => true,
    'exceptions' => true,
    'cache_wsdl' => WSDL_CACHE_NONE
]);

// Analyze request/response
echo "Request Headers:\n" . $client->__getLastRequestHeaders();
echo "Request:\n" . $client->__getLastRequest();
echo "Request Headers:\n" . $client->__getLastRequestHeaders();
echo "Request:\n" . $client->__getLastRequest();
echo "Response Headers:\n" . $client->__getLastResponseHeaders();
echo "Response:\n" . $client->__getLastResponse();

// Connect with logger
$logger->debug('SOAP Debug', [
    'request' => $client->__getLastRequest(),
    'response' => $client->__getLastResponse()
]);
```

## Code Organization

### Directory Structure

```
src/
├── Cache/              # Cache implementations
│   ├── WsdlCache.php
│   └── CacheInterface.php
├── Handler/            # Request handler
│   ├── SoapHandler.php
│   └── WsdlHandler.php
├── Http/              # HTTP communication
│   ├── CurlClient.php
│   └── ConnectionPool.php
├── Logger/            # Logging
│   └── Logger.php
└── SoapProxy.php      # Main class
```

### Coding Standards

- PSR-12 for code style
- PSR-3 for logging
- Strict typing (declare(strict_types=1))
- Type declarations for parameters and return values

### Best Practices

1. **Dependency Injection**
```php
class SoapHandler
{
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->logger = $logger;
        $this->cache = $cache;
    }
}
```

2. **Interface Segregation**
```php
interface CacheInterface
{
    public function get(string $key): ?string;
    public function set(string $key, string $value, int $ttl = null): void;
    public function delete(string $key): void;
}
```

3. **Error Handling**
```php
try {
    $response = $this->client->send($request);
} catch (ConnectionException $e) {
    $this->logger->error('Connection Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    throw new RuntimeException(
        'Connection to SOAP server failed',
        $e->getCode(),
        $e
    );
}
```

## Performance Optimization

### Connection Pooling

```php
class ConnectionPool
{
    private array $connections = [];
    private const MAX_IDLE_TIME = 60;

    public function getConnection(string $host): ?CurlHandle
    {
        $this->cleanupIdleConnections();
        return $this->connections[$host] ?? null;
    }

    private function cleanupIdleConnections(): void
    {
        foreach ($this->connections as $host => $conn) {
            if (time() - $conn['lastUsed'] > self::MAX_IDLE_TIME) {
                unset($this->connections[$host]);
            }
        }
    }
}
```

### WSDL Caching

```php
class WsdlCache
{
    private string $cacheDir;
    private const CACHE_TTL = 3600;

    public function getCached(string $url): ?string
    {
        $file = $this->getCacheFile($url);
        if (!$this->isValid($file)) {
            return null;
        }
        return file_get_contents($file);
    }

    private function isValid(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }
        return (time() - filemtime($file)) < self::CACHE_TTL;
    }
}
```

## Continuous Integration

### GitHub Actions Workflow

```yaml
name: CI

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: curl, dom, xml

    - name: Install Dependencies
      run: composer install

    - name: Run Tests
      run: composer test

    - name: Check Code Style
      run: composer cs-check
```

## Release Process

1. **Increase Version**
```bash
# Adjust composer.json
"version": "1.0.1"
```

2. **Update Changelog**
```markdown
## [1.0.1] - 2025-02-09

### Added
- New feature XYZ

### Fixed
- Bug in ABC fixed
```

3. **Run Tests**
```bash
composer check-all
```

4. **Create Release**
```bash
git tag v1.0.1
git push origin v1.0.1
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Create a Pull Request Headers:\n" . $client->__getLastResponseHeaders();
   echo "Response