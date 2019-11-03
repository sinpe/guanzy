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

/**
 * Environment
 *
 * This class decouples the application from the global PHP environment.
 * This is particularly useful for unit testing.
 */
class Environment
{
    use FactoryTrait;

    /**
     * @var static
     */
    private static $data;

    /**
     * Mock
     */
    public static function mock(array $mock = [])
    {
        //Validates if default protocol is HTTPS to set default port 443
        if (isset($mock['REQUEST_SCHEME']) && $mock['REQUEST_SCHEME'] === 'https') {
            $scheme = 'https';
            $port = 443;
        } else {
            $scheme = 'http';
            $port = 80;
        }

        $data = array_merge([            
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => $scheme,
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => $port,
            'HTTP_HOST' => 'localhost',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'HTTP_USER_AGENT' => 'Guanzy',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $mock);

        static::$data = $data;
    }

    /**
     * __callStatic
     *
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        $implClass = static::$implClass ?? EnvironmentImpl::class;

        if (is_null(static::$instance)) {
            static::$instance = new $implClass(static::$data ?? $_SERVER);
        }

        return call_user_func_array([static::$instance, $method], $params);
    }
}
