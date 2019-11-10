<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Http;

use FastRoute\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Sinpe\Framework\Route\MiddlewareAwareTrait;
use Sinpe\Framework\Route\MiddlewareAwareInterface;
use Sinpe\Framework\Error\InternalErrorResponder;
use Sinpe\Framework\Exception\InternalException;
use Sinpe\Framework\Exception\InternalExceptionResponder;
use Sinpe\Framework\Exception\MethodNotAllowedException;
use Sinpe\Framework\Exception\PageNotFoundException;
use Sinpe\Framework\Route\RouterInterface;
use Sinpe\Framework\Event\RouteFound;

/**
 * Handle the request and output a response
 */
class RequestHandler implements RequestHandlerInterface, MiddlewareAwareInterface
{
    use MiddlewareAwareTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * __construct
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
    private function _run(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->container->get(RouterInterface::class);
        
        $routeInfo = $router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {

            if (container(EventDispatcherInterface::class, true)) {
                container(EventDispatcherInterface::class)
                    ->dispatch(new RouteFound($routeInfo[1]));
            }

            $routeArguments = [];

            foreach ($routeInfo[2] as $k => $v) {
                $routeArguments[$k] = urldecode($v);
            }

            $route = $routeInfo[1];

            $request->setRouteArguments($routeArguments);
            // NOTE froze the request object, After this time, you can not change request!
            $this->container->set(ServerRequestInterface::class, $request);

            return $route->run($request, $routeArguments);
            //
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($routeInfo[1]);
        }

        throw new PageNotFoundException([
            'home' => (string) $request->getUri()->withPath('')->withQuery('')->withFragment('')
        ]);
    }

    protected function run(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // if exception thrown, request changed should be loss.
            $response = $this->_run($request);
        } catch (\Exception $except) {

            $exceptions = config('exceptions');

            if ($except instanceof InternalException) {
                // use default handler
                if (!array_key_exists(get_class($except), $exceptions)) {
                    $responder = $except->getResponder($request);
                }
            }

            if (!isset($responder)) {
                foreach ($exceptions as $targetClass => $responderClass) {
                    // when has a responder class, do it
                    if ($targetClass == get_class($except) || $except instanceof $targetClass) {
                        $responder = new $responderClass($except);
                    }
                }
            }

            if (isset($responder)) {
                $response = $responder->handle($except);
            } else {
                $responder = new InternalExceptionResponder($request);
                $response = $responder->handle($except);
            }
        } catch (\Throwable $except) {
            $responder = new InternalErrorResponder($request);
            $response = $responder->handle($except);
        }

        return $response;
    }
}
