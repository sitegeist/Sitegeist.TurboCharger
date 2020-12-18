<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Flowpack\JobQueue\Common\Annotations as Job;
use Neos\Flow\Http\Middleware\MiddlewaresChain;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Sitegeist\TurboCharger\Http\HttpRequestHandler;

/**
 * Class CacheWarmupService
 * @package Sitegeist\TurboCharger\Service
 * @Flow\Scope("singleton")
 */
class CacheWarmupService
{
    /**
     * @var Bootstrap
     * @Flow\Inject
     */
    protected $bootstrap;

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
        $originalRequestHandler = $this->bootstrap->getActiveRequestHandler();
        try {
            $fakeRequest = new ServerRequest('get', new Uri($uri));
            $fakeRequestHandler = new HttpRequestHandler($this->bootstrap);
            $fakeRequestHandler->setHttpRequest($fakeRequest);
            $this->bootstrap->setActiveRequestHandler($fakeRequestHandler);
            $response = $this->middlewaresChain->handle($fakeRequest);
            $this->logger->info(sprintf('Simulated request for uri "%s" yielded status %s', $uri, $response->getStatusCode()));
        } catch (\Exception $e) {
            $this->logger->info(sprintf('Simulated for uri "%s" yielded exception "%s"', $uri, $e->getMessage()));
        }
        $this->bootstrap->setActiveRequestHandler($originalRequestHandler);
    }
}
