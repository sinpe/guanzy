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

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router
 *
 * This class organizes application route objects. It is responsible
 * for registering route objects, assigning names to route objects,
 * finding routes that match the current HTTP request, and creating
 * URLs for a named route.
 */
class Router implements RouterInterface
{
    private $resolver;
    private $actionStrategy;
    private $uriGetter;

    /**
     * @var array
     */
    protected $dispatchedCache = [];

    /**
     * @var array
     */
    protected $namedCache = [];

    /**
     * Parser
     *
     * @var \FastRoute\RouteParser
     */
    protected $routeParser;

    /**
     * Base path used in pathFor()
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Path to fast route cache file. Set to false to disable route caching
     *
     * @var string|False
     */
    protected $cacheFile = false;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    /**
     * Create new router
     *
     * @param RouteParser   $parser
     */
    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new StdParser;
    }

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return self
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set path to fast route cache file. If this is false then route caching is disabled.
     *
     * @param string|false $cacheFile
     *
     * @return self
     */
    public function setCacheFile($cacheFile)
    {
        if (!is_string($cacheFile) && $cacheFile !== false) {
            throw new \InvalidArgumentException('Router cacheFile must be a string or false');
        }

        $this->cacheFile = $cacheFile;

        if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
            throw new \RuntimeException('Router cacheFile directory must be writable');
        }

        return $this;
    }

    /**
     * Add route
     *
     * @param  string[] $methods Array of HTTP methods
     * @param  string   $pattern The route pattern
     * @param  callable $handler The route callable
     *
     * @return RouteInterface
     *
     * @throws \InvalidArgumentException if the route pattern isn't a string
     */
    public function map($methods, $pattern, $handler)
    {
        if (!is_string($pattern)) {
            throw new \InvalidArgumentException('Route pattern must be a string');
        }

        $pattern = rtrim($pattern, '/');
        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }
        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map("strtoupper", $methods);
        // Add route
        $route = new Route($this, $methods, $pattern, $handler);

        if ($this->routeGroups) {
            $route->setGroups($this->routeGroups);
        }

        if ($this->domains) {
            $route->setDomain($this->domains);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return array
     *
     * @link   https://github.com/nikic/FastRoute/blob/master/src/Dispatcher.php
     */
    public function dispatch(ServerRequestInterface $request)
    {
        if (is_callable($this->uriGetter)) {
            $uri = call_user_func($this->uriGetter, $request);
        } else {
            $uri = '/' . trim($request->getUri()->getPath(), '/');
        }

        if (!isset($this->dispatchedCache[$uri])) {
            $this->dispatchedCache[$uri] = $this->createDispatcher()->dispatch($request->getMethod(), $uri);
        }

        return $this->dispatchedCache[$uri];
    }

    /**
     * @return \FastRoute\Dispatcher
     */
    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $domain = $route->getDomain();
                if ($domain) {
                    $r->addRoute($route->getMethods(), $domain . $this->basePath . $route->getOriginalPattern(), $route);
                } else {
                    $r->addRoute($route->getMethods(), $this->basePath . $route->getOriginalPattern(), $route);
                }
            }
        };

        if ($this->cacheFile) {
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
                'cacheFile' => $this->cacheFile,
            ]);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
            ]);
        }

        return $this->dispatcher;
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return Route
     *
     * @throws \RuntimeException   If named route does not exist
     */
    public function getNamedRoute($name)
    {
        if (isset($this->namedCache[$name])) {
            return $this->namedCache[$name];
        }

        foreach ($this->routes as $route) {
            if ($name == $route->getName()) {
                $this->namedCache[$name] = $route;
                return $route;
            }
        }

        throw new \RuntimeException('Named route does not exist for name: ' . $name);
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups()
    {
        $pattern = '';
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getOriginalPattern();
        }
        return $pattern;
    }

    /**
     * Add a route group to the array
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return GroupInterface
     */
    public function group($pattern, $callable)
    {
        $group = new RouteGroup($this, $pattern, $callable);
        array_push($this->routeGroups, $group);
        $group->run($this);
        array_pop($this->routeGroups);
        return $group;
    }

    /**
     * 
     */
    public function domain($domains, $callable)
    {
        $this->domains = $domains;
        $callable($this);
        $this->domains = null;
        return $this;
    }

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $route = $this->getNamedRoute($name);

        $pattern = $route->getOriginalPattern();

        $routeDatas = $this->routeParser->parse($pattern);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];

        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new \InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function pathFor(string $name, array $data = [], array $queryParams = []): string
    {
        $url = $this->relativePathFor($name, $data, $queryParams);

        if ($this->basePath) {
            $url = $this->basePath . $url;
        }

        return $url;
    }

    /**
     * urlFor
     *
     * @param string $name
     * @param array $data
     * @param array $queryParams
     * @return string
     */
    public function urlFor(string $name, array $data = [], array $queryParams = []): string
    {
        $route = $this->getNamedRoute($name);
        $url = $this->pathFor($name, $data, $queryParams);

        $domain = $route->getDomain();

        if (!empty($domain)) {
            $url = '//' . $domain . $url;
        }

        return $url;
    }

    /**
     * Undocumented function
     *
     * @param [type] $callable
     * @return void
     */
    public function resolve($callable)
    {
        if ($this->resolver) {
            return $this->resolver->resolve($callable);
        }
        return $callable;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function setResolver(CallableResolverInterface $resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * Action strategy
     *
     * @return callable
     */
    public function getActionStrategy(): callable
    {
        if ($this->actionStrategy) {
            return $this->actionStrategy;
        }
        throw new \Exception('route action strategy empty.');
    }

    /**
     * Action strategy
     *
     * @return static
     */
    public function setActionStrategy(callable $strategy)
    {
        $this->actionStrategy = $strategy;
        return $this;
    }

    /**
     * Set URI getter
     *
     * @return static
     */
    public function setUriGetter($uriGetter)
    {
        $this->uriGetter = $uriGetter;
        return $this;
    }
}
