<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use Neos\Flow\Annotations as Flow;
use Flowpack\JobQueue\Common\Annotations as Job;
use Neos\Flow\Http\Middleware\MiddlewaresChain;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

/**
 * Class CacheWarmupService
 * @package Sitegeist\TurboCharger\Service
 * @Flow\Scope("singleton")
 */
class CacheWarmupService
{

    /**
     * @Flow\Inject
     * @var MiddlewaresChain
     */
    protected $middlewaresChain;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string $uri
     * @Job\Defer(queueName="turboCharger")
     */
    public function simulateRequestToUri(string $uri)
    {
        try {
            $request = new ServerRequest('get', new Uri($uri));
            $response = $this->middlewaresChain->handle($request);
            $this->logger->info(sprintf('Simulated request for uri "%s" yielded status %s', $uri, $response->getStatusCode()));
        } catch (\Exception $e) {
            $this->logger->info(sprintf('Simulated for uri "%s" yielded exception "%s"', $uri, $e->getMessage()));
        }
    }
}
