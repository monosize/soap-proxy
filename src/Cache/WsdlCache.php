<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Cache;

use Psr\Log\LoggerInterface;
use RuntimeException;

class WsdlCache
{
    public const CACHE_TTL = 3600; // 1 hour cache duration

    private string $cacheDir;

    private LoggerInterface $logger;

    public function __construct(string $cacheDir, LoggerInterface $logger)
    {
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->ensureCacheDirectory();
    }

    private function ensureCacheDirectory(): void
    {
        if (! file_exists($this->cacheDir)) {
            if (! mkdir($concurrentDirectory = $this->cacheDir, 0755, true) && ! is_dir($concurrentDirectory)) {
                throw new RuntimeException('Could not create cache directory');
            }
        }
    }

    public function getCached(string $url): ?string
    {
        $cacheFile = $this->getCacheFilePath($url);

        if (! file_exists($cacheFile)) {
            return null;
        }

        $cacheTime = filemtime($cacheFile);
        if (time() - $cacheTime > self::CACHE_TTL) {
            unlink($cacheFile);

            return null;
        }

        $this->logger->debug('WSDL Cache hit', ['url' => $url]);

        return file_get_contents($cacheFile);
    }

    public function store(string $url, string $content): void
    {
        $cacheFile = $this->getCacheFilePath($url);

        if (file_put_contents($cacheFile, $content) === false) {
            throw new RuntimeException('Failed to write WSDL cache file');
        }

        $this->logger->debug('WSDL Cached', ['url' => $url]);
    }

    private function getCacheFilePath(string $url): string
    {
        return $this->cacheDir . '/' . md5($url) . '.wsdl';
    }

    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*.wsdl');
        foreach ($files as $file) {
            unlink($file);
        }
        $this->logger->debug('WSDL Cache cleared');
    }
}
