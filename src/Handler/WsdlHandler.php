<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Handler;

use DOMDocument;
use MonoSize\SoapProxy\Cache\WsdlCache;
use MonoSize\SoapProxy\Http\CurlClient;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class WsdlHandler
{
    private SoapProxyRequest $request;

    private WsdlCache $cache;

    private LoggerInterface $logger;

    public function __construct(
        SoapProxyRequest $request,
        LoggerInterface $logger,
        ?WsdlCache $cache = null
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->cache = $cache ?? new WsdlCache(sys_get_temp_dir() . '/wsdl_cache', $logger);
    }

    public function handle(): void
    {
        $targetUrl = $this->request->getTargetUrl();

        // Try to load WSDL from cache
        $wsdlContent = $this->cache->getCached($targetUrl);

        if ($wsdlContent === null) {
            // Not in cache or expired - fetch fresh WSDL
            $credentials = $this->request->getCredentials();

            $curl = new CurlClient($targetUrl, $this->logger);
            $curl->setOptions([
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERPWD => $credentials['user'] . ':' . $credentials['pass'],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_HTTPHEADER => ['Accept: application/xml, text/xml, */*'],
            ]);

            try {
                $wsdlContent = $curl->execute();

                if ($curl->getHttpCode() !== 200 || ! $this->isValidWsdl($wsdlContent)) {
                    throw new RuntimeException('Failed to fetch valid WSDL');
                }

                // Store valid WSDL in cache
                $this->cache->store($targetUrl, $wsdlContent);
                $this->logger->debug('Fresh WSDL fetched and cached');

            } finally {
                $curl->close();
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

        // Check if it's a WSDL document
        $definitions = $doc->getElementsByTagNameNS('http://schemas.xmlsoap.org/wsdl/', 'definitions');

        return $definitions->length > 0;
    }

    private function sendResponse(string $content): void
    {
        if (! headers_sent()) {
            header_remove();
            header('Content-Type: application/wsdl+xml; charset=utf-8');
            header('Content-Length: ' . strlen($content));
            // Cache headers for client
            header('Cache-Control: public, max-age=' . $this->cache::CACHE_TTL);
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $this->cache::CACHE_TTL));
        }

        echo $content;
    }
}
