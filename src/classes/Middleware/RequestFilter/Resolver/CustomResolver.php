<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter\Resolver;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\Middleware\RequestFilter\ResolverInterface;

/**
 * 自定义
 */
class CustomResolver implements ResolverInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * __construct
     *
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * __invoke
     *
     * @param ServerRequestInterface $request
     * @return void
     */
    public function resolve(ServerRequestInterface $request): ServerRequestInterface
    {
        return call_user_func($this->callable, $request);
    }
}
