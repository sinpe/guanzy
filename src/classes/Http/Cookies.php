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

/**
 * Cookie 
 */
class Cookies
{
    /**
     * Cookies from HTTP request
     *
     * @var array
     */
    protected $requestCookies = [];

    /**
     * Cookies for HTTP response
     *
     * @var array
     */
    protected $responseCookies = [];

    /**
     * Default cookie properties
     *
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false,
        'samesite' => null
    ];

    /**
     * Create new cookies helper
     *
     * @param array $cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->requestCookies = $cookies;
    }

    /**
     * Set default cookie properties
     *
     * @param array $settings
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_replace($this->defaults, $settings);
    }

    /**
     * {@inheritDoc}
     */
    public function get($name, $default = null)
    {
        return isset($this->requestCookies[$name]) ? $this->requestCookies[$name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($name, $value)
    {
        if (!is_array($value)) {
            $value = ['value' => (string) $value];
        }
        $this->responseCookies[$name] = array_replace($this->defaults, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function toHeaders(): array
    {
        $headers = [];
        foreach ($this->responseCookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }

        return $headers;
    }

    /**
     * Convert to `Set-Cookie` header
     *
     * @param  string $name       Cookie name
     * @param  array  $properties Cookie properties
     *
     * @return string
     */
    protected function toHeader($name, array $properties)
    {
        $result = urlencode($name) . '=' . urlencode($properties['value']);

        if (isset($properties['domain'])) {
            $result .= '; domain=' . $properties['domain'];
        }

        if (isset($properties['path'])) {
            $result .= '; path=' . $properties['path'];
        }

        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            } else {
                $timestamp = (int) $properties['expires'];
            }
            if ($timestamp !== 0) {
                $result .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (isset($properties['secure']) && $properties['secure']) {
            $result .= '; secure';
        }

        if (isset($properties['hostonly']) && $properties['hostonly']) {
            $result .= '; HostOnly';
        }

        if (isset($properties['httponly']) && $properties['httponly']) {
            $result .= '; HttpOnly';
        }

        if (isset($properties['samesite']) && in_array(strtolower($properties['samesite']), ['lax', 'strict'], true)) {
            // While strtolower is needed for correct comparison, the RFC doesn't care about case
            $result .= '; SameSite=' . $properties['samesite'];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public static function parseHeader($header): array
    {
        if (is_array($header) === true) {
            $header = isset($header[0]) ? $header[0] : '';
        }

        if (is_string($header) === false) {
            throw new \InvalidArgumentException(i18n('can not parse Cookie data. Header value must be a string'));
        }

        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@[;]\s*@', $header);
        $cookies = [];

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
