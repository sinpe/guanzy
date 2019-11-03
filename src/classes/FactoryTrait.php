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
 * Factory
 *
 * This class decouples the application from the global PHP environment.
 * This is particularly useful for unit testing.
 */
trait FactoryTrait
{
    /**
     * @var static
     */
    protected static $instance;

    /**
     * @var string
     */
    protected static $implClass;

    /**
     * setImpl
     *
     * @param string $implClass
     * @return void
     */
    public static function setImpl(string $implClass)
    {
        static::$implClass = $implClass;
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
        if (is_null(static::$instance)) {
            static::$instance = new static::$implClass;
        }

        return call_user_func_array([static::$instance, $method], $params);
    }
}
