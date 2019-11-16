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
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 
 */
class RouteHandler implements RequestHandlerInterface, MiddlewareAwareInterface
{
    use MiddlewareAwareTrait;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Callable
     */
    private $callable;

    /**
     * __construct
     *
     * @param RouterInterface $router
     */
    public function __construct(
        RouterInterface $router,
        $callable
    ) {
        $this->router = $router;
        $this->callable = $callable;
    }

    /**
     * Dispatch route callable against current ServerRequest and Response objects
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request  The current ServerRequest object
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception  if the route callable throws an exception
     */
    protected function run(ServerRequestInterface $request)
    {
        $container = container(ContainerInterface::class);
        // NOTE froze the request object, After this time, you can not change request!
        $container->set(ServerRequestInterface::class, $request);

        $response = call_user_func(
            $this->router->getInvoker(),
            $this->router->resolve($this->callable)
        ); 

        if (!$response instanceof ResponseInterface) {
            throw new \Exception(sprintf('route callback MUST return a %s.', ResponseInterface::class));
        }

        return $response;
    }
}
