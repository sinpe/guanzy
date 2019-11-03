<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS
 * 
 * @author sinpe <18222544@qq.com>
 */
class Cors implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $options = [
        'origin' => '*',
        'allowMethods' => 'GET,HEAD,PUT,POST,DELETE',
        // 'maxAge' =>
        // 'exposeHeaders' =>
        // 'allowHeaders' =>
        // 'allowCredentials' =>
    ];

    /**
     * __construct
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Execute as PSR-7 double pass middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $response = $this->setCorsHeaders($request, $response);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function setCorsHeaders(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // 
        $response = $this->setOrigin($request, $response);
        $response = $this->setAllowMethods($response);
        $response = $this->setAllowCredentials($response);

        if ($request->isOptions()) {
            // $response = $this->setOrigin($request, $response);
            $response = $this->setMaxAge($response);
            // $response = $this->setAllowCredentials($request, $response);
            // $response = $this->setAllowMethods($request, $response);
            $response = $this->setAllowHeaders($request, $response);
        } else {
            // $this->setOrigin($request, $response);
            $response = $this->setExposeHeaders($response);
            // $response = $this->setAllowCredentials($request, $response);
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setOrigin(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        //
        $origin = $this->options['origin'];

        if (is_callable($origin)) {
            // Call origin callback with request origin
            $origin = call_user_func(
                $origin,
                $request->getHeaderLine('Origin')
            );
        }

        // handle multiple allowed origins
        if (is_array($origin)) {

            $allowedOrigins = $origin;

            // default to the first allowed origin
            $origin = reset($allowedOrigins);

            // but use a specific origin if there is a match
            foreach ($allowedOrigins as $allowedOrigin) {
                if ($allowedOrigin === $request->getHeaderLine('Origin')) {
                    $origin = $allowedOrigin;
                    break;
                }
            }
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setExposeHeaders(ResponseInterface $response): ResponseInterface
    {
        // 
        if (isset($this->options['exposeHeaders'])) {
            $exposeHeaders = $this->options['exposeHeaders'];
            if (is_array($exposeHeaders)) {
                $exposeHeaders = implode(", ", $exposeHeaders);
            }

            $response = $response->withHeader('Access-Control-Expose-Headers', $exposeHeaders);
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setMaxAge(ResponseInterface $response): ResponseInterface
    {
        if (isset($this->options['maxAge'])) {
            $response = $response->withHeader('Access-Control-Max-Age', $this->options['maxAge']);
        }
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setAllowCredentials(ResponseInterface $response): ResponseInterface
    {
        if (isset($this->options['allowCredentials']) && $this->options['allowCredentials'] === true) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setAllowMethods(ResponseInterface $response): ResponseInterface
    {

        if (isset($this->options['allowMethods'])) {
            $allowMethods = $this->options['allowMethods'];
            if (is_array($allowMethods)) {
                $allowMethods = implode(', ', $allowMethods);
            }

            $response = $response->withHeader('Access-Control-Allow-Methods', $allowMethods);
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function setAllowHeaders(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if (isset($this->options['allowHeaders'])) {
            $allowHeaders = $this->options['allowHeaders'];
            if (is_array($allowHeaders)) {
                $allowHeaders = implode(", ", $allowHeaders);
            }
        } else {  // Otherwise, use request headers
            $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        }

        if (isset($allowHeaders)) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $allowHeaders);
        }

        return $response;
    }
}
