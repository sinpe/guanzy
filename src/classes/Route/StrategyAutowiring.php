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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 基于命名参数和自动注入的路由调度方式
 */
class StrategyAutowiring implements StrategyInterface
{
    /**
     * di container
     *
     * @param ContainerInterface $container
     */
    private $container;

    /**
     * __construct
     *
     * @param ContainerInterface $container The di container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param array|callable         $callable
     *
     * @return mixed
     */
    public function process(
        callable $callable
    ):ResponseInterface {
        return $this->container->call($callable);
    }
}
