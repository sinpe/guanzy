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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Router Interface
 */
interface RouterInterface
{
    // array keys from route result
    const DISPATCH_STATUS = 0;
    const ALLOWED_METHODS = 1;

    /**
     * Add route
     *
     * @param string[] $methods Array of HTTP methods
     * @param string   $pattern The route pattern
     * @param callable $handler The route callable
     *
     * @return RouteInterface
     */
    public function map($methods, $pattern, $handler);

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array
     *
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request);

    /**
     * Add a route group to the array
     *
     * @param string   $pattern The group pattern
     * @param callable $callable A group callable
     *
     * @return GroupInterface
     */
    public function group($pattern, $callable);

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return RouteInterface
     *
     * @throws \RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name);

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function relativePathFor(string  $name, array $data = [], array $queryParams = []): string;

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function pathFor(string  $name, array $data = [], array $queryParams = []): string;

    /**
     * Undocumented function
     *
     * @param [type] $callable
     * @return void
     */
    public function resolve($callable);

    /**
     * Undocumented function
     *
     * @return void
     */
    public function setResolver(CallableResolverInterface $resolver);

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getActionStrategy();

    /**
     * Undocumented function
     *
     * @return void
     */
    public function setActionStrategy($strategy);
}
