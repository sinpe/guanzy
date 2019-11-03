<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Route;

/**
 * A collector for Routable objects with a common middleware stack
 */
class RouteGroup extends Routable implements RouteGroupInterface
{
    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * Create a new RouteGroup
     *
     * @param string   $pattern  The pattern prefix for the group
     * @param callable $callable The group callable
     */
    public function __construct(
        $router,
        $pattern,
        $callable
    ) {
        $this->router = $router;
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * 中间件
     */
    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddlewareStack(): iterable
    {
        return $this->middlewares;
    }

    /**
     * Invoke the group to register any Routable objects within it.
     *
     * @param Application $app The App instance to bind/pass to the group callable
     */
    public function run()
    {
        $callable = $this->router->resolve($this->callable);

        $callable();
    }
}
