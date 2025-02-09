<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MonoSize\SoapProxy\SoapProxy;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a basic logger
$logger = new Logger('soap-proxy');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

// Create proxy with direct configuration
$proxy = new SoapProxy(
    $logger,
    'https://target-soap-server.com',
    true // debug mode
);

// Handle the request
$proxy->handle();

// Alternative: Create from environment
$proxyFromEnv = SoapProxy::createFromEnv($logger);
$proxyFromEnv->handle();