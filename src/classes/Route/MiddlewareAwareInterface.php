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

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareAwareInterface
{
    /**
     * Add a middleware to the stack
     *
     * @param mixed $middleware
     *
     * @return static
     */
    public function add($middleware) : MiddlewareAwareInterface;

    /**
     * Add multiple middleware to the stack
     *
     * @param \Psr\Http\Server\MiddlewareInterface[] $middlewares
     *
     * @return static
     */
    public function addMany(array $middlewares) : MiddlewareAwareInterface;

    /**
     * Prepend a middleware to the stack
     *
     * @param mixed $middleware
     *
     * @return static
     */
    public function prepend($middleware) : MiddlewareAwareInterface;

    /**
     * Shift a middleware from beginning of stack
     *
     * @return \Psr\Http\Server\MiddlewareInterface|null
     */
    public function shift() : MiddlewareInterface;

    // /**
    //  * Get the stack of middleware
    //  *
    //  * @return \Psr\Http\Server\MiddlewareInterface[]
    //  */
    // public function getMiddlewareStack() : iterable;
}
