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
use Psr\Http\Message\ServerRequestInterface;

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
     * Route params handler
     *
     * @var callable
     */
    private $argsHandler;

    /**
     * __construct
     *
     * @param ContainerInterface $container The di container
     */
    public function __construct(
        ContainerInterface $container,
        callable $argsHandler = null
    ) {
        $this->container = $container;
        $this->argsHandler = $argsHandler;
    }

    /**
     * Invoke a route callable with request, response and all route parameters
     * as individual arguments.
     *
     * @param array|callable         $callable
     * @param ServerRequestInterface $request
     * @param array                  $routeArguments
     *
     * @return mixed
     */
    public function process(
        callable $callable,
        ServerRequestInterface $request,
        array $routeArguments = []
    ):ResponseInterface {
		// 三个固定名字的参数
        $args['request'] = $request;
        // 
        if (is_callable($this->argsHandler)) { // custom from your apply
            $args['args'] = call_user_func($this->argsHandler, $routeArguments);
        } else {
            $args['args'] = $routeArguments;
        }
        
        return $this->container->call($callable, $args);
    }
}
