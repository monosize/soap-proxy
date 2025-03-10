<?php

declare(strict_types=1);

namespace MonoSize\SoapProxy\Handler;

use DOMDocument;
use MonoSize\SoapProxy\Config\Environment;
use MonoSize\SoapProxy\Http\CurlClient;
use MonoSize\SoapProxy\Request\SoapProxyRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SoapHandler
{
    private SoapProxyRequest $request;

    private LoggerInterface $logger;

    private Environment $environment;

    public function __construct(
        SoapProxyRequest $request,
        LoggerInterface $logger,
        Environment $environment
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    public function handle(): void
    {
        $rawPost = $this->request->getRawPostData();
        $this->logger->debug('Raw POST data', ['data' => $rawPost]);

        $soapVersion = $this->determineSoapVersion();
        $soapAction = $this->extractSoapAction($soapVersion, $rawPost);

        $credentials = $this->request->getCredentials();

        $curl = new CurlClient($this->request->getTargetUrl(), $this->logger, $this->environment);
        $curl->setOptions([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $rawPost,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . ($soapVersion === '1.2' ? 'application/soap+xml' : 'text/xml') . '; charset=utf-8',
                'Authorization: Basic ' . base64_encode($credentials['user'] . ':' . $credentials['pass']),
                'SOAPAction: ' . $soapAction,
            ],
        ]);

        try {
            $response = $curl->execute();
            $httpCode = $curl->getHttpCode();

            // Log the SOAP response
            $this->logger->debug('SOAP Response', [
                'http_code' => $httpCode,
                'content_length' => strlen($response),
                'is_valid_xml' => $this->isValidXml($response),
                'soap_version' => $soapVersion,
                'response_content' => $response,
            ]);

            if ($httpCode === 200 && $this->isValidXml($response)) {
                $this->sendResponse($response, $soapVersion);
            } else {
                throw new RuntimeException('Invalid SOAP response');
            }
        } finally {
            $curl->close();
        }
    }

    private function determineSoapVersion(): string
    {
        return isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/soap+xml') ? '1.2' : '1.1';
    }

    private function extractSoapAction(string $soapVersion, string $rawPost): string
    {
        if ($soapVersion === '1.1') {
            return $_SERVER['HTTP_SOAPACTION'] ?? '';
        }

        $doc = new DOMDocument();
        if (@$doc->loadXML($rawPost)) {
            $actionNodes = $doc->getElementsByTagNameNS('http://www.w3.org/2005/08/addressing', 'Action');

            return $actionNodes->length > 0 ? $actionNodes->item(0)->nodeValue : '';
        }

        return '';
    }

    private function isValidXml(string $content): bool
    {
        $doc = new DOMDocument();

        return @$doc->loadXML($content) !== false;
    }

    private function sendResponse(string $content, string $soapVersion): void
    {
        if (! headers_sent()) {
            $contentType = $soapVersion === '1.2' ? 'application/soap+xml' : 'text/xml';
            header("Content-Type: {$contentType}; charset=utf-8");
            header('Content-Length: ' . strlen($content));
        }

        echo $content;
    }
}
