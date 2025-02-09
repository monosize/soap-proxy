<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Http;

class CurlConnectionPool
{
    private static array $connections = [];
    private const MAX_IDLE_TIME = 60; // Maximum idle time in seconds

    public static function getConnection(string $host): ?\CurlHandle
    {
        self::cleanupIdleConnections();

        $key = self::getConnectionKey($host);

        if (isset(self::$connections[$key])) {
            $conn = self::$connections[$key];
            if (self::isConnectionValid($conn['handle'])) {
                return $conn['handle'];
            }
            self::removeConnection($key);
        }

        return null;
    }

    public static function storeConnection(string $host, \CurlHandle $handle): void
    {
        $key = self::getConnectionKey($host);
        self::$connections[$key] = [
            'handle' => $handle,
            'lastUsed' => time(),
            'host' => $host,
        ];
    }

    private static function getConnectionKey(string $host): string
    {
        return md5($host);
    }

    private static function isConnectionValid(\CurlHandle $handle): bool
    {
        curl_setopt($handle, CURLOPT_NOBODY, true);

        return curl_exec($handle) !== false;
    }

    private static function cleanupIdleConnections(): void
    {
        $now = time();
        foreach (self::$connections as $key => $conn) {
            if ($now - $conn['lastUsed'] > self::MAX_IDLE_TIME) {
                self::removeConnection($key);
            }
        }
    }

    private static function removeConnection(string $key): void
    {
        if (isset(self::$connections[$key])) {
            curl_close(self::$connections[$key]['handle']);
            unset(self::$connections[$key]);
        }
    }

    public static function closeAll(): void
    {
        foreach (self::$connections as $conn) {
            curl_close($conn['handle']);
        }
        self::$connections = [];
    }
}
