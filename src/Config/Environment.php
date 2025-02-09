<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Config;

use RuntimeException;

class Environment
{
    private array $env = [];

    public function __construct(?string $envPath = null)
    {
        $this->loadDotEnv($envPath);
    }

    private function loadDotEnv(?string $envPath = null): void
    {
        if (class_exists('\Dotenv\Dotenv')) {
            // Use provided path or default to parent directory
            $path = $envPath ?? dirname(getcwd());

            $dotenv = \Dotenv\Dotenv::createImmutable($path);

            try {
                $dotenv->load();
                $this->env = $_ENV;
            } catch (\Exception $e) {
                // Fallback to existing environment variables if .env file doesn't exist
                $this->env = getenv();
            }
        } else {
            $this->env = getenv();
        }
    }

    public function get(string $key, string $default = ''): string
    {
        $envValue = $this->env[$key] ?? $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $envValue !== false ? (string)$envValue : $default;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if (empty($value)) {
            throw new RuntimeException("Required environment variable {$key} is not set");
        }

        return $value;
    }
}