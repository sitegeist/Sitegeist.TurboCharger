<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Service;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Neos\Neos\Controller\CreateContentContextTrait;
use Sitegeist\TurboCharger\Http\HttpRequestHandler;

#[Flow\Scope('singleton')]
class SchedulingService
{
    use CreateContentContextTrait;

    #[Flow\Inject]
    protected Bootstrap $bootstrap;

    #[Flow\Inject]
    protected CacheWarmupService $cacheWarmupService;

    #[Flow\InjectConfiguration(path: 'http.baseUri', package: 'Neos.Flow')]
    protected ?string $baseUri;

    #[Flow\InjectConfiguration(path: 'enabled')]
    protected bool $enabled;

    /** @var array<string, UriInterface> */
    protected array $pendingUrisToScheduleRequest = [];

    protected UriBuilder $uriBuilder;

    public function initializeObject() {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof HttpRequestHandlerInterface) {
            $this->enabled = false;
            return;
        }
        $actionRequest = ActionRequest::fromHttpRequest($requestHandler->getHttpRequest());
        $this->uriBuilder = new UriBuilder();
        $this->uriBuilder->setRequest($actionRequest);
        $this->uriBuilder
            ->setFormat('html')
            ->setCreateAbsoluteUri(true);
    }

    public function afterNodePublishing(NodeInterface $node, Workspace $targetWorkspace): void
    {
        if ($this->enabled === false) {
            return;
        }

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

        if (!$liveNode || !$liveNode->isVisible() || !$liveNode->isAccessible()) {
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
            }
        }
    }

    public function allObjectsPersisted(): void
    {
        if ($this->enabled === false) {
            return;
        }

        if ($this->pendingUrisToScheduleRequest) {
            foreach ($this->pendingUrisToScheduleRequest as $nodeContextPath => $uri) {
                $this->cacheWarmupService->simulateRequestToUri((string)$uri);
            }
            $this->pendingUrisToScheduleRequest = [];
        }
    }
}
