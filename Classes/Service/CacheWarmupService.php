<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Middleware\MiddlewaresChainFactory;
use Flowpack\JobQueue\Common\Annotations as Job;
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
     * @var MiddlewaresChainFactory
     */
    protected $middlewaresChainFactory;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow", path="http.middlewares")
     * @var array
     */
    protected $middlewaresChainConfiguration;

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
            $simulatedRequest = new ServerRequest('get', new Uri($uri));
            $requestHandler = new HttpRequestHandler($this->bootstrap);
            $requestHandler->setHttpRequest($simulatedRequest);
            $this->bootstrap->setActiveRequestHandler($requestHandler);
            sleep(3);
            $middlewaresChain = $this->middlewaresChainFactory->create($this->middlewaresChainConfiguration);
            $simulatedResponse = $middlewaresChain->handle($simulatedRequest);
            $this->logger->info(sprintf('Simulated request for uri "%s" yielded status %s', $uri, $simulatedResponse->getStatusCode()));
        } catch (\Exception $e) {
            $this->logger->info(sprintf('Simulated for uri "%s" yielded exception "%s"', $uri, $e->getMessage()));
        }
        $this->bootstrap->setActiveRequestHandler($originalRequestHandler);
    }
}
