<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter\Resolver;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\Middleware\RequestFilter\ResolverInterface;

/**
 * Basic认证
 */
class BasicAuthenticator implements ResolverInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * __construct
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        /* Default options. */
        $this->options = [
            // 
            'do' => function ($user, $password) {

                if (empty($user)) {
                    return false;
                }

                $reference = $this->options['users'][$user];

                if (preg_match("/^\$(2|2a|2y)\$\d{2}\$.*/", $reference) && (strlen($reference) >= 60)) {
                    /* Hashed password. */
                    return password_verify($password, $reference);
                } else {
                    return $reference === $password;
                }
            },
        ];

        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * resolve
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function resolve(ServerRequestInterface $request): ServerRequestInterface
    {
        $user = null;
        $password = null;

        if (preg_match("/Basic\s+(.*)$/i", $request->getHeaderLine('Authorization'), $matches)) {
            $explodedCredential = explode(':', base64_decode($matches[1]), 2);
            if (count($explodedCredential) == 2) {
                list($user, $password) = $explodedCredential;
            }
        }

        /* Check if user authenticates. */
        if (false === $this->options['do']($user, $password)) {
            throw new BasicAuthenticatorException();
        }

        return $request;
    }
}
