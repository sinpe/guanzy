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

use Sinpe\Event\Event;
use Sinpe\Framework\Route\RouteInterface;

/**
 * Event of application when Route found, needs EventDispatcher supporting.
 */
class RouteFound extends Event
{
    /**
     * @var RouteInterface
     */
    private $route;

    /**
     * __construct
     *
     * @param RouteInterface $route
     */
    public function __construct(RouteInterface $route)
    {
        $this->route = $route;
    }

    /**
     * Return "Route" to callee by this.
     *
     * @return RouteInterface
     */
    public function getRoute(): RouteInterface
    {
        return $this->route;
    }
}
