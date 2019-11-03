<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Event;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Event\Event;

/**
 * Event of application when Run begin, needs EventDispatcher supporting.
 * You can do something with request, and return a request copy.
 */
class AppRunBegin extends Event
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * __construct
     *
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Return "ServerRequest" to callee by this.
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * When "ServerRequest" attachs some attributes, you can reset "ServerRequest" for callee by this.
     * 
     * @return void
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }
}
