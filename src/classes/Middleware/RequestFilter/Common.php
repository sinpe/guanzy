<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 通用型
 */
class Common implements MiddlewareInterface
{
    /**
     * @var \Closure
     */
    protected $prepare;

    /**
     * @var RuleInterface
     */
    protected $rules;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * __construct
     *
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->rules = new \SplStack;
        $this->resolver = $resolver;
    }

    /**
     * Add rule
     *
     * @param RuleInterface $rule
     * @return static
     */
    public function addRule(RuleInterface $rule)
    {
        $this->rules->push($rule);
        return $this;
    }

    /**
     * Execute as PSR-7 double pass middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // prepare some thing
        if (is_callable($this->prepare)) {
            call_user_func($this->prepare, $request);
        }

        // If rules say we should not authenticate call next and return. 
        if (false === $this->should($request)) {
            return $handler->handle($request);
        }

        $request = $this->resolver->resolve($request);

        return $handler->handle($request);
    }

    /**
     * @param \Closure $prepare
     * @return void
     */
    public function prepare(\Closure $prepare)
    {
        $this->prepare = $prepare;
    }

    /**
     * Test if current request should be authenticated.
     */
    protected function should(ServerRequestInterface $request): bool
    {
        // If any of the rules in stack return false will not authenticate 
        foreach ($this->rules as $callable) {
            if (false === $callable->check($request)) {
                return false;
            }
        }
        return true;
    }
}
