<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Log\LoggerInterface;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * Class SchedulingService
 * @package Sitegeist\TurboCharger\Service
 * @Flow\Scope("singleton")
 */
class SchedulingService
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var CacheWarmupService
     */
    protected $cacheWarmupService;

    /**
     * @Flow\InjectConfiguration(path="http.baseUri", package="Neos.Flow")
     * @var string
     */
    protected $baseUri;

    /**
     * @var Uri[]
     */
    protected $pendingUrisToScheduleRequest = [];

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    public function initializeObject() {
        $baseUri = $this->baseUri ?? 'http://localhost';
        $httpRequest = new ServerRequest('GET', $baseUri);
        $actionRequest = ActionRequest::fromHttpRequest($httpRequest);

        $this->uriBuilder = new UriBuilder();
        $this->uriBuilder->setRequest($actionRequest);
        $this->uriBuilder
            ->setFormat('html')
            ->setCreateAbsoluteUri(true);
    }

    /**
     * @param NodeInterface $node The node was published
     * @param Workspace $targetWorkspace
     */
    public function scheduleForCachePreheating(NodeInterface $node, Workspace $targetWorkspace): void
    {
        if ($targetWorkspace->isPublicWorkspace() === false) {
            return;
        }

        // traverse up to closest document
        while (!$node->getNodeType()->isOfType('Neos.Neos:Document') && $node) {
            $node = $node->getParent();
        }

        if (!$node || !$node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return;
        }

        $liveContext = $this->createContentContext('live', $node->getContext()->getDimensions());
        $liveNode = $liveContext->getNodeByIdentifier((string)$node->getNodeAggregateIdentifier());

        if (!$liveNode) {
            return;
        }

        $nodeContextPath = $liveNode->getContextPath();
        if (!array_key_exists($nodeContextPath, $this->pendingUrisToScheduleRequest)) {
            try {
                $uri = $this->uriBuilder->uriFor(
                    'show',
                    ['node' => $liveNode],
                    'Frontend\\Node',
                    'Neos.Neos'
                );
                $this->pendingUrisToScheduleRequest[$nodeContextPath] = $uri;
            } catch (\Exception $e) {
                $this->logger->error(sprintf('could not schedule node "%s" for cache preheating because no url could be created', $nodeContextPath));
            }
        }
    }

    /**
     * @return void
     */
    public function scheduleCachePreheatingJobs(): void
    {
        if ($this->pendingUrisToScheduleRequest) {
            foreach ($this->pendingUrisToScheduleRequest as $nodeContextPath => $uri) {
                $this->logger->info(sprintf('schedule node "%s" uri "%s" for cache preheating', $nodeContextPath, $uri));
                $this->cacheWarmupService->simulateRequestToUri((string)$uri);
            }
            $this->pendingUrisToScheduleRequest = [];
            $this->persistenceManager->persistAll();
        }
    }
}
