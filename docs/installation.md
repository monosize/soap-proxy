# Installation
## Via Composer
The simplest way to install is via Composer:
```bash
composer require monosize/soap-proxy
```
## Manual Installation
1. Clone the repository:
```bash
git clone https://github.com/monosize/soap-proxy.git
```
2. Install dependencies:
```bash
cd soap-proxy
composer install
```
## Directory Structure Setup
Recommended directory structure for your project:
```
project/
├── public/
│   └── soap-proxy.php    # Entry point
├── logs/
│   └── .gitkeep
├── .env                  # Configuration
└── composer.json
```
## Create Entry Point
Create the file `public/soap-proxy.php`:
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use MonoSize\SoapProxy\SoapProxy;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
$logger = new Logger('soap-proxy');
$logger->pushHandler(new StreamHandler('../logs/soap-proxy.log', Logger::DEBUG));
try {
    $proxy = SoapProxy::createFromEnv($logger);
    $proxy->handle();
} catch (Throwable $e) {
    $logger->error('SOAP Proxy Error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    throw $e;
}
```
## Configure Web Server
### Apache (.htaccess)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/soap-proxy/(.*)
    RewriteRule ^soap-proxy/(.*)$ soap-proxy.php [L,QSA]
</IfModule>
```
### Nginx (nginx.conf)
```nginx
location /soap-proxy {
    try_files $uri $uri/ /soap-proxy.php?$args;
}
```
## Permissions
1. Log directory:
```bash
chmod 755 logs
chown www-data:www-data logs
```
2. Configuration file:
```bash
chmod 640 .env
chown www-data:www-data .env
```
## Check Installation
1. Test WSDL retrieval:
```bash
curl -u username:password "http://your-domain.com/soap-proxy/service?wsdl"
```
2. Check log file:
```bash
tail -f logs/soap-proxy.log
```
## Next Steps
- [Configuration](configuration.md) adjustment
- [Usage](usage.md) familiarization