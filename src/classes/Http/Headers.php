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

use Sinpe\Framework\ArrayObject;

/**
 * This class represents a collection of HTTP headers
 * that is used in both the HTTP request and response objects.
 * It also enables header name case-insensitivity when
 * getting or setting a header value.
 *
 * Each HTTP header can have multiple values. This class
 * stores values into an array for each header name. When
 * you request a header value, you receive an array of values
 * for that header.
 * 
 */
class Headers extends ArrayObject implements HeadersInterface
{
    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    final public function __construct($items = [])
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Return array of HTTP header names and values.
     * This method returns the _original_ header name
     * as specified by the end user.
     *
     * @return array
     */
    public function all()
    {
        $out = [];

        foreach ($this as $key => $props) {
            $out[$props['originalKey']] = $props['value'];
        }

        return $out;
    }

    /**
     * Set HTTP header value
     *
     * This method sets a header value. It replaces
     * any values that may already exist for the header name.
     *
     * @param string $key   The case-insensitive header name
     * @param string $value The header value
     */
    public function set($key, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        parent::set($this->getNormalizedName($key), [
            'value' => $value,
            'originalKey' => $key
        ]);
    }

    /**
     * Get HTTP header value
     *
     * @param  string  $key     The case-insensitive header name
     * @param  mixed   $default The default value if key does not exist
     *
     * @return string[]
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->getNormalizedName($key))['value'];
        }

        return $default;
    }

    /**
     * Get HTTP header key as originally specified
     *
     * @param  string   $key     The case-insensitive header name
     * @param  mixed    $default The default value if key does not exist
     *
     * @return string
     */
    public function getOriginalKey($key, $default = null)
    {
        if ($this->has($key)) {
            return parent::get($this->getNormalizedName($key))['originalKey'];
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function add($key, $value)
    {
        $oldValues = $this->get($key, []);
        $newValues = is_array($value) ? $value : [$value];
        $this->set($key, array_merge($oldValues, array_values($newValues)));
    }

    /**
     * Does this collection have a given header?
     *
     * @param  string $key The case-insensitive header name
     *
     * @return bool
     */
    public function has($key)
    {
        return parent::has($this->getNormalizedName($key));
    }

    /**
     * Remove header from collection
     *
     * @param  string $key The case-insensitive header name
     */
    public function remove($key)
    {
        parent::remove($this->getNormalizedName($key));
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizedName($key)
    {
        // TODO 多次调用计算，待优化

        $key = strtr(strtolower($key), '_', '-');

        if (strpos($key, 'http-') === 0) {
            $key = substr($key, 5);
        }

        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));
    }
}
