<?php
declare(strict_types=1);

namespace Sitegeist\TurboCharger\Http;

use Neos\Flow\Http\RequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class HttpRequestHandler extends RequestHandler
{
    /**
     * @param ServerRequestInterface $httpRequest
     */
    public function setHttpRequest(ServerRequestInterface $httpRequest): void
    {
        $this->httpRequest = $httpRequest;
    }

}
