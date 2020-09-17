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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Route
 */
class Route extends Routable implements RouteInterface
{
    /**
     * @var string
     */
    protected $user = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * Route name
     *
     * @var null|string
     */
    protected $name;

    /**
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    /**
     * scheme
     */
    protected $scheme;

    /**
     * host
     */
    protected $host;

    /**
     * port
     */
    protected $port;

    /**
     * The callable payload
     *
     * @var callable
     */
    protected $callable;

    /**
     * Create new route
     *
     * @param string|string[]   $methods The route HTTP methods
     * @param string            $pattern The route pattern
     * @param callable          $callable The route callable
     * @param RouteGroup[]      $groups The parent route groups
     */
    public function __construct(
        $router,
        $methods,
        $pattern,
        $callable
    ) {
        $this->router = $router;
        $this->methods = is_string($methods) ? [$methods] : $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
    }

    /**
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Set parent route groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set user and password
     */
    public function setUserInfo($user, $password)
    {
        $this->user = $user;
        $this->password = $password;

        return $this;
    }

    /**
     * 
     */
    protected function getAuthority(): string
    {
        $userInfo = $this->user;

        if (!empty($this->password)) {
            $userInfo .= ':' . $this->password;
        }

        return ($userInfo !== '' ? $userInfo . '@' : '') .
            $this->host . (!empty($this->port) && $this->port != 80 && $this->port != 443 ? ':' . $this->port : '');
    }

    /**
     * Set host
     */
    public function setHost(string $host, int $port = 80, string $scheme = null)
    {
        $this->host = $host;
        $this->port = $port;

        if (!empty($scheme)) {
            $this->scheme = str_replace('://', '', strtolower($scheme));
        }

        return $this;
    }

    /**
     * Get scheme and authority
     */
    public function getSchemeAuthority(): ?string
    {
        $authority = $this->getAuthority();

        return (!empty($this->scheme) ? $this->scheme . ':' : '')
            . ($authority !== '' ? '//' . $authority : '');
    }

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return self
     *
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * 中间件
     */
    public function add($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function run(
        ServerRequestInterface $request
    ): ResponseInterface {

        $handler = new RouteHandler($this->router, $this->callable);

        $handler->addMany(array_reverse($this->middlewares));
        // 绑定group的中间件
        $groupMiddlewares = [];

        foreach ($this->getGroups() ?? [] as $group) {
            $groupMiddlewares = array_merge($groupMiddlewares, $group->getMiddlewareStack());
        }

        foreach (array_reverse($groupMiddlewares) as $middleware) {
            $handler->prepend($middleware);
        }
        // Traverse middleware stack and fetch updated response
        return $handler->handle($request);
    }
}
