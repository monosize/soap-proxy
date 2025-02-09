# Configuration
## Environment Variables
The library is primarily configured using a `.env` file:
### Required Settings
| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| TRANSFERHOST | URL of the target SOAP server | - | Yes |
| PROXYDEBUG | Enable debug logging (0/1) | 0 | No |

### Optional Settings
| Variable | Description | Default |
|----------|-------------|---------|
| LOG_LEVEL | Log level (debug/info/error) | error |
| WSDL_CACHE_DIR | WSDL cache directory | system temp |
| WSDL_CACHE_TTL | Cache validity in seconds | 3600 |
| SSL_VERIFY_PEER | Verify SSL peer | false |
| SSL_VERIFY_HOST | Verify SSL host | false |

## Example .env
```env
# Required settings
TRANSFERHOST=https://target-soap-server.com
PROXYDEBUG=0
# Logging
LOG_LEVEL=error
LOG_PATH=/path/to/logs
# Cache
WSDL_CACHE_DIR=/path/to/wsdl_cache
WSDL_CACHE_TTL=3600
# SSL
SSL_VERIFY_PEER=false
SSL_VERIFY_HOST=false
```

## Configure Logger
The library uses PSR-3 compatible loggers. Example with Monolog:
```php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;

$logger = new Logger('soap-proxy');
// File handler with rotation
$logger->pushHandler(new RotatingFileHandler(
    'logs/soap-proxy.log',
    30,  // Keep logs for 30 days
    Logger::DEBUG
));
// Stdout handler for development
if (getenv('APP_ENV') === 'development') {
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
}
```

## Web Server Configuration
### Apache
Virtual Host Configuration:
```apache
<VirtualHost *:80>
    ServerName soap-proxy.example.com
    DocumentRoot /var/www/soap-proxy/public
    <Directory /var/www/soap-proxy/public>
        AllowOverride None
        Require all granted
        
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ soap-proxy.php [QSA,L]
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/soap-proxy_error.log
    CustomLog ${APACHE_LOG_DIR}/soap-proxy_access.log combined
</VirtualHost>
```

### Nginx
Server Block Configuration:
```nginx
server {
    listen 80;
    server_name soap-proxy.example.com;
    root /var/www/soap-proxy/public;
    location / {
        try_files $uri $uri/ /soap-proxy.php?$args;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index soap-proxy.php;
        include fastcgi_params;
    }
}
```

## Performance Optimization
### WSDL Caching
```env
# Optimize cache settings
WSDL_CACHE_DIR=/tmp/wsdl_cache
WSDL_CACHE_TTL=86400  # 24 hours
```

### Connection Pooling
```env
# Connection pool settings
CURL_KEEPALIVE=1
CURL_KEEPIDLE=60
CURL_KEEPINTVL=60
```

## Security Settings
```env
# Enable SSL validation (for production)
SSL_VERIFY_PEER=true
SSL_VERIFY_HOST=true
# Additional security
PROXY_TIMEOUT=30
MAX_REDIRECTS=5
```