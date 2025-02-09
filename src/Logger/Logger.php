<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    private string $logFile;

    private bool $debug;

    public function __construct(string $logFile, bool $debug = false)
    {
        $this->logFile = $logFile;
        $this->debug = $debug;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (! $this->debug && $level === LogLevel::DEBUG) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextString = ! empty($context) ? PHP_EOL . print_r($context, true) : '';
        $logMessage = "[$timestamp][$level] $message$contextString" . PHP_EOL;

        error_log($logMessage, 3, $this->logFile);
    }
}
