# Troubleshooting

## Common Issues

### 1. Authentication Errors

#### Problem
```
SOAP Proxy Error: Authentication required
```

#### Solution
- Verify Basic Auth credentials
- Check the transmission of authentication headers
- Ensure credentials are correctly set in the SOAP client:
```php
$client = new SoapClient($wsdlUrl, [
    'login' => 'username',
    'password' => 'password'
]);
```

### 2. WSDL Not Reachable

#### Problem
```
SOAP Proxy Error: Failed to fetch valid WSDL
```

#### Solution
- Check the TRANSFERHOST environment variable
- Verify reachability of the target server
- Check SSL/TLS settings
- Clear and rebuild cache:
```php
$cache->clear();
```

### 3. SSL/TLS Issues

#### Problem
```
cURL Error: SSL certificate problem: unable to get local issuer certificate
```

#### Solution
- Development: Disable SSL verification (only for development!)
```env
SSL_VERIFY_PEER=false
SSL_VERIFY_HOST=false
```

- Production: Install correct SSL certificate
```env
SSL_VERIFY_PEER=true
SSL_VERIFY_HOST=true
SSL_CAFILE=/path/to/cacert.pem
```

### 4. Performance Issues

#### Problem
- Slow response times
- High server load

#### Solution
- Enable WSDL caching and increase TTL
```env
WSDL_CACHE_TTL=86400  # 24 hours
```

- Optimize connection pooling
```env
CURL_KEEPALIVE=1
CURL_KEEPIDLE=60
CURL_KEEPINTVL=60
```

### 5. Memory Issues

#### Problem
```
PHP Fatal error: Allowed memory size exhausted
```

#### Solution
- Increase PHP memory limit in php.ini:
```ini
memory_limit = 256M
```

- Efficiently process large WSDL files:
```php
// Enable streaming
libxml_set_streams_context(stream_context_create([
    'http' => ['timeout' => 30]
]));
```

## Enable Debug Mode

In .env file:
```env
PROXYDEBUG=1
```
This will automatically set the log level to DEBUG. When PROXYDEBUG=0, only errors will be logged.
Check log output:

Check log output:
```bash
tail -f logs/soap-proxy.log
```

## Log Analysis

### Important Log Messages

1. Connection Issues:
```
[ERROR] Connection to target server failed
```
- Check network connection
- Verify firewall settings

2. Cache Issues:
```
[ERROR] Failed to write WSDL cache file
```
- Check directory permissions
- Verify disk space

3. WSDL Validation:
```
[ERROR] Invalid WSDL document received
```
- Manually check WSDL document
- Perform XML validation

## Permission Issues

### Log Directory

```bash
# Correct permissions
chmod 755 logs
chown www-data:www-data logs

# Permissions for log files
find logs -type f -exec chmod 644 {} \;
```

### Cache Directory

```bash
# Set up cache directory
mkdir -p /path/to/wsdl_cache
chown www-data:www-data /path/to/wsdl_cache
chmod 755 /path/to/wsdl_cache
```

## Performance Analysis

### Connection Monitoring

```php
// Debug output for connections
$logger->debug('Connection stats', [
    'time' => curl_getinfo($curl, CURLINFO_TOTAL_TIME),
    'dns' => curl_getinfo($curl, CURLINFO_NAMELOOKUP_TIME),
    'connect' => curl_getinfo($curl, CURLINFO_CONNECT_TIME)
]);
```

### Cache Efficiency

```php
// Analyze cache hit rate
$logger->debug('Cache status', [
    'hits' => $cacheHits,
    'misses' => $cacheMisses,
    'ratio' => ($cacheHits / ($cacheHits + $cacheMisses)) * 100
]);
```

## Contact Support

If you cannot resolve the issues yourself:

1. Collect log files
2. Verify configuration
3. Document error messages
4. Create a GitHub issue with:
    - PHP version
    - Library version
    - Error description
    - Log excerpts
    - Minimal example for reproduction