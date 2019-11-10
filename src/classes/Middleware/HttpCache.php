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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sinpe\Framework\Exception\NotModifiedException;

class HttpCache
{
    private $denyCache;

    /**
     * @var array
     */
    protected $options = [
        // Cache-Control type (public or private)
        'type' => 'private', // 'private', 'public'
        // Cache-Control max age in seconds
        'max_age' => 0,
        // Cache-Control includes must-revalidate flag
        'must_revalidate' => false,
        'expires' => 0,
        // ETag type: "strong" or "weak"
        'etag_type' => 'strong',
        'etag' => '',
        'last_modified' => '',
    ];

    /**
     * __construct
     *
     * @param bool $cache
     * @param array $options
     */
    public function __construct(bool $cache, $options = [])
    {
        $this->denyCache = !$cache;

        if ($cache) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Invoke cache middleware
     *
     * @param  RequestInterface  $request  A PSR7 request object
     * @param  ResponseInterface $response A PSR7 response object
     * @param  callable          $next     The next middleware callable
     *
     * @return ResponseInterface           A PSR7 response object
     */
    /**
     * Execute as PSR-7 double pass middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // ETag header and conditional GET check
        $etag = $this->options['etag'];

        if (!$this->denyCache && $etag) {
            // 
            $etagType = $this->options['etag_type'];

            if (!in_array($etagType, ['strong', 'weak'])) {
                throw new \InvalidArgumentException('Invalid etag type. Must be "strong" or "weak".');
            }

            $etag = '"' . $etag . '"';
            if ($etagType === 'weak') {
                $etag = 'W/' . $etag;
            }

            $ifNoneMatch = $request->getHeaderLine('If-None-Match');
            if ($ifNoneMatch) {
                $etagList = preg_split('@\s*,\s*@', $ifNoneMatch);
                if (in_array($etag, $etagList) || in_array('*', $etagList)) {
                    throw new NotModifiedException();
                }
            }
        }

        // Last-Modified header and conditional GET check
        $lastModified = $this->options['last_modified'];

        if (!$this->denyCache && $lastModified) {
            if (!is_integer($lastModified)) {
                $lastModified = strtotime($lastModified);
                if ($lastModified === false) {
                    throw new \InvalidArgumentException('Last Modified value could not be parsed with `strtotime()`.');
                }
            }
            $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
            if ($ifModifiedSince && $lastModified <= strtotime($ifModifiedSince)) {
                throw new NotModifiedException();
            }
        }

        $response = $handler->handle($request);

        if ($this->denyCache) {
            return $response->withHeader('Cache-Control', 'no-store,no-cache');
        }

        // Cache-Control header
        if (!$response->hasHeader('Cache-Control')) {
            if ($this->options['max_age'] === 0) {
                $response = $response->withHeader('Cache-Control', sprintf(
                    '%s, no-cache%s',
                    $this->options['type'],
                    $this->options['must_revalidate'] ? ', must-revalidate' : ''
                ));
            } else {
                $response = $response->withHeader('Cache-Control', sprintf(
                    '%s, max-age=%s%s',
                    $this->options['type'],
                    $this->options['max_age'],
                    $this->options['must_revalidate'] ? ', must-revalidate' : ''
                ));
            }
        }

        // Last-Modified header and conditional GET check
        if ($lastModified) {
            $response->withHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $lastModified));
        }

        if ($etag) {
            $response = $response->withHeader('ETag', $etag);
        }

        $expires = $this->options['expires'];

        if (!is_integer($expires)) {
            $expires = strtotime($expires);
            if ($expires === false) {
                throw new \InvalidArgumentException('Expiration value could not be parsed with `strtotime()`.');
            }
        }
        $response = $response->withHeader('Expires', gmdate('D, d M Y H:i:s T', $expires));

        return $response;
    }
}
