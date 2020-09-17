<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework;

/**
 * Dependency injection container.
 */
class Container extends \Sinpe\Container\Container
{
    /**
     * Register the default items.
     */
    protected function registerDefaults()
    {
        $container = $this;

        if (!isset($container[Route\RouterInterface::class])) {

            /**
             * @return Route\RouterInterface
             */
            $container[Route\RouterInterface::class] = function () use ($container) {

                $routerCacheFile = false;

                if (config()->has('runtime.route_cache')) {
                    $routerCacheFile = config('runtime.route_cache')(); // function
                }

                $router = (new Route\Router())->setCacheFile($routerCacheFile);

                $router->setBasePath(Environment::getBasePath());

                $router->setResolver(new CallableResolver($container));
                $router->setInvoker(function($callable) use ($container) {
                    return $container->call($callable);
                });

                return $router;
            };
        }
    }
}
