<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 认证中间件
 */
class Authentication extends Common
{
    /**
     * @var array
     */
    protected $options = [
        'secure' => true,
        'relaxed' => [
            'localhost',
            '127.0.0.1'
        ],
        // 固定用户
        'users' => null,
        // includes
        'includes' => null,
        // 
        'excludes' => null,
        // 
        'realm' => 'Protected',
    ];

    /**
     * __construct
     *
     * @param ResolverInterface $resolver
     * @param array $options
     */
    public function __construct(ResolverInterface $resolver, $options = [])
    {
        parent::__construct($resolver);
        /* Store passed in options overwriting any defaults */
        $this->hydrate($options);
    }

    /**
     * Execute as PSR-7 double pass middleware.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();
        $scheme = $request->getUri()->getScheme();
        // $server_params = $request->getServerParams();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->should($request)) {
            return $handler->handle($request);
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ('https' !== $scheme && true === $this->options['secure']) {

            $allowedHost = in_array($host, $this->options['relaxed']);

            /* if 'headers' is in the 'relaxed' key, then we check for forwarding */
            $allowedForward = false;

            if (in_array('headers', $this->options['relaxed'])) {
                if (
                    $request->getHeaderLine('X-Forwarded-Proto') === 'https'
                    && $request->getHeaderLine('X-Forwarded-Port') === '443'
                ) {
                    $allowedForward = true;
                }
            }

            if (!($allowedHost || $allowedForward)) {
                $message = sprintf(
                    'insecure use of middleware over %s denied by configuration.',
                    strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        $request = $this->resolver->resolve($request);

        return $handler->handle($request);
    }

    /**
     * Set the users array.
     */
    private function users(array $users): void
    {
        $this->options['users'] = $users;
    }

    /**
     * Set the secure flag.
     */
    private function secure(bool $secure): void
    {
        $this->options['secure'] = $secure;
    }

    /**
     * Set hosts where secure rule is relaxed.
     */
    private function relaxed(array $relaxed): void
    {
        $this->options['relaxed'] = $relaxed;
    }
}
