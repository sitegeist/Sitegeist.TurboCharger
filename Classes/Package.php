<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger;

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\ContentRepository\Domain\Model\Workspace;
use Sitegeist\TurboCharger\Service\SchedulingService;

class Package extends BasePackage
{
    /**
    * @param Bootstrap $bootstrap The current bootstrap
    * @return void
    */
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Workspace::class, 'afterNodePublishing', SchedulingService::class, 'scheduleForCachePreheating');
        $dispatcher->connect(PersistenceManager::class, 'allObjectsPersisted', SchedulingService::class, 'scheduleCachePreheatingJobs');
    }

}
