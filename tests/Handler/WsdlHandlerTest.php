<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Tests\Handler;

use MonoSize\SoapProxy\Cache\WsdlCache;
use MonoSize\SoapProxy\Config\Environment;
use MonoSize\SoapProxy\Handler\WsdlHandler;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WsdlHandlerTest extends TestCase
{
    private $request;

    private $logger;

    private $cache;

    private $environment;

    private $handler;

    protected function setUp(): void
    {
        $this->request = $this->createMock(SoapProxyRequest::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(WsdlCache::class);
        $this->environment = $this->createMock(Environment::class);

        // Configure default environment behavior
        $this->environment
            ->method('get')
            ->willReturnMap([
                ['SSL_VERIFY_PEER', 'false', 'false'],
                ['SSL_VERIFY_HOST', 'false', 'false'],
            ]);

        $this->handler = new WsdlHandler(
            $this->request,
            $this->logger,
            $this->cache,
            $this->environment
        );
    }

    public function testHandleWithCachedWsdl(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn('http://example.com/service?wsdl');

        $this->cache
            ->expects($this->once())
            ->method('getCached')
            ->willReturn('<?xml version="1.0"?><definitions></definitions>');

        ob_start();
        $this->handler->handle();
        $output = ob_get_clean();

        $this->assertStringContainsString('<definitions>', $output);
    }

    public function testHandleWithFreshWsdl(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn('http://example.com/service?wsdl');

        $this->cache
            ->expects($this->once())
            ->method('getCached')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn(['user' => 'test', 'pass' => 'test']);

        // Note: This test would need more setup for the CurlClient
        // In a real test, you might want to mock the CurlClient or use a test double
        $this->expectException(\RuntimeException::class);

        $this->handler->handle();
    }

    public function testHandleWithSSLVerification(): void
    {
        // Configure environment to enable SSL verification
        $environment = $this->createMock(Environment::class);
        $environment
            ->method('get')
            ->willReturnMap([
                ['SSL_VERIFY_PEER', 'false', 'true'],
                ['SSL_VERIFY_HOST', 'false', 'true'],
            ]);

        $handler = new WsdlHandler(
            $this->request,
            $this->logger,
            $this->cache,
            $environment
        );

        $this->request
            ->expects($this->once())
            ->method('getTargetUrl')
            ->willReturn('https://example.com/service?wsdl');

        $this->cache
            ->expects($this->once())
            ->method('getCached')
            ->willReturn(null);

        $this->request
            ->expects($this->once())
            ->method('getCredentials')
            ->willReturn(['user' => 'test', 'pass' => 'test']);

        // The test will fail with RuntimeException since we can't make actual HTTPS calls in tests
        $this->expectException(\RuntimeException::class);

        $handler->handle();
    }
}
