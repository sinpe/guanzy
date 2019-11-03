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
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareAwareTrait
{
    /**
     * @var \Psr\Http\Server\MiddlewareInterface[]
     */
    protected $middlewares = [];

    /**
     * @var \Psr\Http\Server\MiddlewareInterface[]
     */
    protected $whenStacks = [];

    /**
     * {@inheritdoc}
     */
    public function add($middleware): MiddlewareAwareInterface
    {
        if ($middleware instanceof \Closure) {
            $middleware = new ClosureMiddleware($middleware);
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMany(array $middlewares): MiddlewareAwareInterface
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prepend($middleware): MiddlewareAwareInterface
    {
        if ($middleware instanceof \Closure) {
            $middleware = new ClosureMiddleware($middleware);
        }

        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMiddleware()
    {
        return count($this->middlewares) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function shift(): MiddlewareInterface
    {
        return array_shift($this->middlewares);
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function getMiddlewareStack() : iterable
    // {
    //     return $this->middlewares;
    // }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->whenStacks) > 0) {
            foreach ($this->whenStacks as $index => $middleware) {
                // assert
                if ($middleware->assert($request)) {
                    array_splice($this->whenStacks, $index, 1);
                    return $middleware->process($request, $this);
                }
            }
        }

        if ($this->hasMiddleware()) {
            $middleware = $this->shift();
            //
            if ($middleware instanceof WhenMiddleware) {
                // assert
                if ($middleware->assert($request)) {
                    return $middleware->process($request, $this);
                } else {
                    // waiting
                    $this->whenStacks[] = $middleware;
                    
                    return $this->handle($request);
                }
            } else {
                return $middleware->process($request, $this);
            }
        } else {
            return $this->run($request);
        }
    }
}
