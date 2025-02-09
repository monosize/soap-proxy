<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Handler;

use DOMDocument;
use MonoSize\SoapProxy\Cache\WsdlCache;
use MonoSize\SoapProxy\Config\Environment;
use MonoSize\SoapProxy\Http\CurlClient;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class WsdlHandler
{
    private SoapProxyRequest $request;

    private WsdlCache $cache;

    private LoggerInterface $logger;

    private Environment $environment;

    public function __construct(
        SoapProxyRequest $request,
        LoggerInterface $logger,
        WsdlCache $cache,
        Environment $environment
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->environment = $environment;
    }

    public function handle(): void
    {
        $targetUrl = $this->request->getTargetUrl();
        $this->logger->debug('Handling WSDL request', ['target_url' => $targetUrl]);

        // Try to load WSDL from cache
        $wsdlContent = $this->cache->getCached($targetUrl);

        if ($wsdlContent === null) {
            try {
                $credentials = $this->request->getCredentials();
                $this->logger->debug('Using credentials', [
                    'username' => $credentials['user'],
                    'has_password' => ! empty($credentials['pass']),
                ]);

                $curl = new CurlClient($targetUrl, $this->logger, $this->environment);
                $curl->setOptions([
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_USERPWD => $credentials['user'] . ':' . $credentials['pass'],
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_HTTPHEADER => ['Accept: application/xml, text/xml, */*'],
                ]);

                $wsdlContent = $curl->execute();
                $httpCode = $curl->getHttpCode();

                if ($httpCode !== 200 || ! $this->isValidWsdl($wsdlContent)) {
                    throw new RuntimeException('Failed to fetch valid WSDL');
                }

                // Store valid WSDL in cache
                $this->cache->store($targetUrl, $wsdlContent);
                $this->logger->debug('Fresh WSDL fetched and cached');

            } finally {
                if (isset($curl)) {
                    $curl->close();
                }
            }
        }

        $this->sendResponse($wsdlContent);
    }

    private function isValidWsdl(string $content): bool
    {
        $doc = new DOMDocument();
        if (! @$doc->loadXML($content)) {
            return false;
        }

        $definitions = $doc->getElementsByTagNameNS('http://schemas.xmlsoap.org/wsdl/', 'definitions');

        return $definitions->length > 0;
    }

    private function sendResponse(string $content): void
    {
        if (! headers_sent()) {
            header_remove();
            header('Content-Type: application/wsdl+xml; charset=utf-8');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: public, max-age=' . $this->cache::CACHE_TTL);
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $this->cache::CACHE_TTL));
        }

        echo $content;
    }
}
