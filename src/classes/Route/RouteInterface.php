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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Route Interface
 */
interface RouteInterface
{
    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getOriginalPattern();

    // /**
    //  * Set a route argument
    //  *
    //  * @param string $name
    //  * @param string $value
    //  *
    //  * @return self
    //  */
    // public function setArgument($name, $value);

    // /**
    //  * Replace route arguments
    //  *
    //  * @param string[] $arguments
    //  *
    //  * @return self
    //  */
    // public function setArguments(array $arguments);

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function setName($name);

    // /**
    //  * Prepare the route for use
    //  *
    //  * @param ServerRequestInterface $request
    //  * @param array $arguments
    //  */
    // public function prepare(ServerRequestInterface $request, array $arguments);

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request);
}
