<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use Neos\Flow\Annotations as Flow;
use Flowpack\JobQueue\Common\Annotations as Job;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Sitegeist\TurboCharger\Http\HttpRequestHandler;

#[Flow\Scope('singleton')]
class CacheWarmupService
{
    #[Flow\InjectConfiguration(path: 'enabled')]
    protected bool $enabled;

    #[Flow\InjectConfiguration(path: 'internalBaseUrl')]
    protected ?string $internalBaseUrl;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Job\Defer(queueName: 'sitegeist-turbocharger')]
    public function simulateRequestToUri(string $uri)
    {
        if ($this->enabled === false) {
            return;
        }
        $parsedUrl = parse_url($uri);
        if ($this->internalBaseUrl !== null) {
            $internalUrl = $this->internalBaseUrl . $parsedUrl[ 'path' ] . (isset($parsedUrl[ 'query' ]) ? '?' . $parsedUrl[ 'query' ] : '');
            $headers = ['Host: ' . $parsedUrl['host'], 'X-Forwarded-Proto: ' . ($parsedUrl['scheme'] ?? 'https')];
        } else {
            $internalUrl = $uri;
            $headers = [];
        }
        $curlHandle = curl_init($internalUrl);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Sitegeist.TurboCharger; +https://www.sitegeist.de)');
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_exec($curlHandle);
        $responseCode = curl_getinfo( $curlHandle,CURLINFO_RESPONSE_CODE);
        if ($responseCode === 200) {
            $this->logger->info(sprintf('Cache warmup of %s via %s, headers %s succeeded ', $uri, $internalUrl, json_encode($headers, JSON_THROW_ON_ERROR)));
        } else {
            $this->logger->error(sprintf('Cache warmup of %s via %s, headers %s failed!', $uri, $internalUrl, json_encode($headers, JSON_THROW_ON_ERROR)));
        }
    }
}
