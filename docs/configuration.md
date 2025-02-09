# Configuration
## Environment Variables
The library is primarily configured using a `.env` file:
### Required Settings
| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| TRANSFERHOST | URL of the target SOAP server | - | Yes |
| PROXYDEBUG | Enable debug mode (0/1) | 0 | No |

### Optional Settings
| Variable | Description | Default |
|----------|-------------|---------|
| SSL_VERIFY_PEER | Verify SSL peer | false |
| SSL_VERIFY_HOST | Verify SSL host | false |

## Example .env
```env
# Required settings
TRANSFERHOST=https://target-soap-server.com
PROXYDEBUG=0

# SSL
SSL_VERIFY_PEER=false
SSL_VERIFY_HOST=false
```

## Configure Logger
The library uses PSR-3 compatible loggers. The log level is automatically controlled by the PROXYDEBUG environment variable. When PROXYDEBUG=1, all debug messages are logged. When PROXYDEBUG=0, only errors are logged.

Example with Monolog:
```php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$logger = new Logger('soap-proxy');
$logger->pushHandler(new RotatingFileHandler(
    'logs/soap-proxy.log',
    30  // Keep logs for 30 days
));

// Log level will be automatically set based on PROXYDEBUG environment variable
$proxy = SoapProxy::createFromEnv($logger, $cacheDir);
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

## Security Settings
```env
# Enable SSL validation (for production)
SSL_VERIFY_PEER=true
SSL_VERIFY_HOST=true
```