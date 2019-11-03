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
 * ArrayObject
 */
class ArrayObject extends \ArrayObject
{
    /**
     * __construct
     *
     * @param mixed $input
     */
    public function __construct($input = [])
    {
        parent::__construct($input);
        $this->setFlags(\ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Set object item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Get object item for key
     *
     * @param string $key     The data key
     * @param mixed  $default The default value to return if data key does not exist
     * @return mixed The key's value, or the default value
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->offsetGet($key) : $default;
    }

    /**
     * Get object keys
     *
     * @return array The object's source data keys
     */
    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }

    /**
     * Does this object have a given key?
     *
     * @param string $key The data key
     * @return bool
     */
    public function has($key)
    {
        return parent::offsetExists($key);
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
