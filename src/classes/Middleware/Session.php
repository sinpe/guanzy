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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Session middleware
 */
class Session
{
    /**
     * @var array
     */
    protected $options = [
        'lifetime'     => '30 minutes',
        'path'         => '/',
        'domain'       => null,
        'secure'       => false,
        'httponly'     => false,
        'name'         => '__session__',
        'autorefresh'  => false,
        'handler'      => null,
        'ini_settings' => [],
    ];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $options = array_merge($this->options, $options);

        if (is_string($lifetime = $options['lifetime'])) {
            $options['lifetime'] = strtotime($lifetime) - time();
        }

        $this->options = $options;

        $this->iniSet($options['ini_settings']);

        // Just override this, to ensure package is working
        if (ini_get('session.gc_maxlifetime') < $options['lifetime']) {
            $this->iniSet([
                'session.gc_maxlifetime' => $options['lifetime'] * 2,
            ]);
        }
    }

    /**
     * Called when middleware needs to be executed.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $this->startSession($request);
        return $handler->handle($request);
    }

    /**
     * Start session
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function startSession(ServerRequestInterface $request): ServerRequestInterface
    {
        $inactive = session_status() === PHP_SESSION_NONE;

        if (!$inactive) {
            return $request;
        }

        $options = $this->options;
        $name = $options['name'];

        session_set_cookie_params(
            $options['lifetime'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly']
        );

        // Refresh session cookie when "inactive",
        // else PHP won't know we want this to refresh

        $cookieName = $request->getCookieParam($name);

        if ($options['autorefresh'] && !empty($cookieName)) {
            setcookie(
                $name,
                $cookieName,
                time() + $options['lifetime'],
                $options['path'],
                $options['domain'],
                $options['secure'],
                $options['httponly']
            );
        }

        session_name($name);

        $handler = $options['handler'];

        if ($handler) {
            if (!($handler instanceof \SessionHandlerInterface)) {
                $handler = new $handler;
            }
            session_set_save_handler($handler, true);
        }

        session_cache_limiter(false);
        session_start();

        return $request;
    }

    /**
     * @param [type] $options
     * @return void
     */
    protected function iniSet($options)
    {
        foreach ($options as $key => $val) {
            if (strpos($key, 'session.') === 0) {
                ini_set($key, $val);
            }
        }
    }
}
