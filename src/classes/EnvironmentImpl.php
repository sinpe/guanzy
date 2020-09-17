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
 * EnvImpl
 *
 * This class decouples the application from the global PHP environment.
 * This is particularly useful for unit testing.
 */
class EnvironmentImpl extends ArrayObject
{
    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var Http\Uri
     */
    private $uri;

    /**
     * @var string
     */
    private $scriptPath;

    /**
     * When you using proxy and has a prefix path
     * 
     * @var string
     */
    private $virtualPath = '';

    /**
     * Sources for host, You can override by your subclass
     * @var array
     */
    protected $hostLiterals = [
        'HTTP_X_FORWARDED_HOST',
        'X-FORWARDED-HOST',
        'HTTP_X_FORWARDED_SERVER',
        'X-FORWARDED-SERVER',
        'HTTP_HOST',
        'SERVER_NAME'
    ];

    /**
     * Sources for scheme, You can override by your subclass
     * @var array
     */
    protected $schemeInspects = [
        'HTTP_X_FORWARDED_PROTO',
        'X-FORWARDED-PROTO',
        'REQUEST_SCHEME',
    ];

    /**
     * Get scheme from environment.
     */
    public function getScheme(): string
    {
        if (!$this->scheme) {
            // 
            $this->scheme = 'http';

            $literals = $this->schemeInspects;

            foreach ((array) $literals as $item) {
                if ($this->has($item)) {
                    $this->scheme = $this->get($item);
                    break;
                }
            }
        }

        return $this->scheme;
    }

    /**
     * Get host from environment.
     */
    public function getHost(): string
    {
        if (!$this->host) {
            $host = '127.0.0.1';
            $literals = $this->hostLiterals;

            foreach ((array) $literals as $item) {
                if ($this->has($item)) {
                    $host = $this->get($item);
                    break;
                }
            }

            if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
                $host = $matches[1];
                if (isset($matches[2])) {
                    $this->port = (int) substr($matches[2], 1);
                }
            } else {
                $pos = strpos($host, ':');
                if ($pos !== false) {
                    $this->port = (int) substr($host, $pos + 1);
                    $host = strstr($host, ':', true);
                }
            }

            $this->host = $host;
        }

        return $this->host;
    }

    /**
     * Get port from environment.
     */
    public function getPort(): ?int
    {
        if (!$this->port) {
            $this->port = $this->has('SERVER_PORT') ? (int) $this->get('SERVER_PORT') : 80;
        }

        return $this->port;
    }

    /**
     * Get userinfo from environment.
     */
    public function getUserInfo(): string
    {
        return trim(implode(':', [$this->get('PHP_AUTH_USER', ''), $this->get('PHP_AUTH_PW', '')]), ':');
    }

    /**
     * Get authority from environment.
     */
    public function getAuthority(): string
    {
        return ltrim($this->getUserInfo() . '@', '@') . $this->getDomain();
    }

    /**
     * Get domain from environment.
     */
    public function getDomain(): string
    {
        $port = $this->getPort();

        return  trim(implode(':', [$this->getHost(), $port !== 80 && $port !== 443 ? $port : '']), ':');
    }

    /**
     * Get method from environment.
     */
    public function getMethod(): string
    {
        if (!$this->has('REQUEST_METHOD')) {
            throw new \Exception('cannot determine HTTP method');
        }

        return $this->get('REQUEST_METHOD') ?? 'GET';
    }

    /**
     * Get uri from environment.
     *
     * [scheme:][//authority][path][?query][#fragment]
     * 
     * @return Http\Uri
     */
    public function getUri(): Http\Uri
    {
        if (!$this->uri) {
            // Scheme
            $scheme = $this->getScheme();
            // Authority: Username and password
            $authority = $this->getAuthority();

            // Query string
            $queryString = '';
            if ($this->has('QUERY_STRING')) {
                $queryString = $this->get('QUERY_STRING');
            }

            // Request URI
            $path = '';
            if ($this->has('REQUEST_URI')) {
                $uriFragments = explode('?', $this->get('REQUEST_URI'));
                $path = $uriFragments[0];
                if ($queryString === '' && count($uriFragments) > 1) {
                    $queryString = parse_url('http://www.example.com' . $this->get('REQUEST_URI'), PHP_URL_QUERY) ?? '';
                }
            }

            $queryString = rtrim('?' . $queryString, '?');

            $this->uri = new Http\Uri("{$scheme}://{$authority}{$path}{$queryString}");
        }

        return $this->uri;
    }

    /**
     * Get scriptPath from environment.
     *
     * @return string
     */
    public function getBasePath(): ?string
    {
        if (!$this->scriptPath) {

            $scriptName = $this->get('SCRIPT_NAME');
            $scriptPath = str_replace('\\', '/', dirname($scriptName));

            $requestUri = $this->get('REQUEST_URI');

            $this->scriptPath = '';

            // raw, not rewrited
            if (stripos($requestUri, $scriptName) === 0) {
                $this->scriptPath = $scriptName;
            } elseif ($scriptPath !== '/' && stripos($requestUri, $scriptPath) === 0) { // 多级且rewrited
                $this->scriptPath = $scriptPath;
            }
        }

        return $this->scriptPath . ($this->virtualPath ? '/' . $this->virtualPath : '');
    }

    /**
     * Get base url from environment.
     *
     * @return string
     */
    public function getBaseUrl(): ?string
    {
        return rtrim($this->getScheme() . '://' . $this->getAuthority() . '/' . $this->getBasePath(), '/');
    }

    /**
     * Set virtual path
     *
     * ex：a proxy prefix path
     * 
     * @param string $path
     * @return void
     */
    public function setVirtualPath(string $path = '')
    {
        return $this->virtualPath = trim($path, '/\\');
    }

    /**
     * Get full uri
     *
     * @return string
     */
    public function urlFor($path, $scheme = true): string
    {
        return ($scheme ? $this->getScheme() . '://' : '//') . $this->getAuthority()
            . ($this->virtualPath ? '/' . $this->virtualPath : '') . '/' . trim($path, '/\\');
    }

    // /**
    //  * Cut base path
    //  *
    //  * @return string
    //  */
    // public function cutBasePath($path): string
    // {
    //     if (strpos($path, $this->getBasePath()) === 0) {
    //         return substr($path, 0, strlen($this->getBasePath()));
    //     }

    //     return $path;
    // }
}
