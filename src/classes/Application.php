<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// functions and consts.
require_once __DIR__ . '/../defines.php';
require_once __DIR__ . '/../helpers.php';

/**
 * This is the primary class with which you instantiate,
 * configure, and run a Sinpe Framework application.
 */
class Application
{
    protected $domains = [];

    /**
     * Application middlewares.
     * 
     * @var array
     */
    protected $middlewares = [];

    /**
     * __construct
     */
    public function __construct()
    {
        set_error_handler(function ($errno, $errstr) {
            throw new \Exception($errstr, $errno);
        }, error_reporting());

        set_exception_handler(function ($except) {
            $request = new Http\ServerRequest(
                Environment::getMethod(),
                Environment::getUri(),
                Environment::toArray()
            );
            $responder = new Error\InternalErrorResponder($request);
            $response = $responder->handle($except);
            $response->flush($request);
        });

        // container instance
        $container = container();
        // 
        if (!$container instanceof ContainerInterface) {
            throw new \Exception(i18n('container return Must be %s', ContainerInterface::class));
        }

        // config instance
        $config = $this->genConfig();
        if (!$config || !method_exists($config, 'get')) {
            throw new \Exception(i18n(
                '%s::genConfig return Must has "get" method',
                static::class
            ));
        }
        $container->set('config', $config);
        $container->set(get_class($config), $config);

        $config->load(__DIR__ . '/../runtime.php');
    }

    /**
     * Config factory, You MUST override this method.
     * 
     * @return object
     */
    protected function genConfig(): object
    {
        throw new \Exception(i18n('%s needs to be overrided', __METHOD__));
    }

    /**
     * Add "GET" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function get($pattern, $callable)
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Add "POST" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function post($pattern, $callable)
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Add "PUT" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function put($pattern, $callable)
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Add "PATCH" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function patch($pattern, $callable)
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Add "DELETE" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function delete($pattern, $callable)
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Add "OPTIONS" route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function options($pattern, $callable)
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     */
    public function any($pattern, $callable)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  callable|string    $callable The route callback routine
     */
    public function map(array $methods, $pattern, $callable)
    {
        if ($callable instanceof \Closure) {
            // bind container for $this
            $callable = $callable->bindTo(container());
        }
        
        $route = container(Route\RouterInterface::class)->map($methods, $pattern, $callable);

        $domain = $route->getDomain();
        if (empty($domain)) {
            $route->setDomain(Environment::getDomain());
        }

        return $route;
    }

    /**
     * Bind domain for some routes
     *
     * @param string|array   $domain
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function domain($domain, $callable)
    {
        return container(Route\RouterInterface::class)->domain($domain, $callable);
    }

    /**
     * Add a route that sends an HTTP redirect
     *
     * @param string              $from
     * @param string|UriInterface $to
     * @param int                 $status
     */
    public function redirect($from, $to, $status = 302)
    {
        return $this->get($from, function (Http\Responder $responder) use ($to, $status) {
            return $responder->handle()->withHeader('Location', (string) $to)->withStatus($status);
        });
    }

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function group($pattern, $callable)
    {
        return container(Route\RouterInterface::class)->group($pattern, $callable);
    }

    /**
     * Add middleware
     */
    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param bool $silent
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws PageNotFoundException
     */
    public function run(bool $silent = false): ResponseInterface
    {
        $request = new Http\ServerRequest(
            Environment::getMethod(),
            Environment::getUri(),
            Environment::toArray()
        );

        if (container(EventDispatcherInterface::class, true)) {
            $request = container(EventDispatcherInterface::class)
                ->dispatch(new Event\AppRunBegin($request))->getRequest();
        }

        // If debug, can echo directly.
        if (APP_DEBUG) {
            ob_start();
        }
        // Traverse middleware stack
        $requestHandler = new Http\RequestHandler(container());
        //
        $requestHandler->addMany(array_reverse($this->middlewares));
        // if exception thrown, request changed should be loss.
        $response = $requestHandler->handle($request);

        // If debug, can echo directly.
        if (APP_DEBUG) {
            $output = ob_get_clean();
        }

        // Event
        if (container(EventDispatcherInterface::class, true)) {
            $response = container(EventDispatcherInterface::class)
                ->dispatch(new Event\AppRunEnd($response))->getResponse();
        }

        // If debug, can echo directly.
        if (APP_DEBUG && !empty($output) && $response->getBody()->isWritable()) {
            // append output buffer content
            $response->getBody()->write($output);
        }

        $response = $this->finalize($request, $response);

        if ($request->isOptions()) {
            $response = $response->withStatus(200)->withHeader('Content-Type', 'text/plain');
        }

        if (!$silent) {
            $response->flush($request);
        }

        return $response;
    }

    /**
     * Finalize response
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function finalize(ServerRequestInterface $request, ResponseInterface $response)
    {
        // stop PHP sending a Content-Type automatically
        ini_set('default_mimetype', '');
        // clear the body if this is a HEAD request
        if ($request->isHead()) {
            return $response->withBody(new Http\EmptyStream('r'));
        }

        if ($response->isEmpty() && !$request->isHead()) {
            return $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        if (ob_get_length() > 0) {
            throw new \RuntimeException(i18n("unexpected data in output buffer. " .
                "Maybe you have characters before an opening <?php tag?"));
        }

        $size = $response->getBody()->getSize();

        if ($size !== null && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', (string) $size);
        }

        return $response;
    }
}
