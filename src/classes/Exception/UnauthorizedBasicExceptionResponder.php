<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe Inc. <dev@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Responder for 401.
 */
class UnauthorizedBasicExceptionResponder extends BadRequestExceptionResponder
{
    /**
     * __construct
     * 
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);

        $this->subscribeResponse(function (ResponseInterface $response) {

            $context = $this->getContext();
            // 
            $response = $response->withStatus(401)->withHeader(
                'WWW-Authenticate',
                sprintf('Basic realm="%s"', $context['realm'])
            );

            return $response;
        });
    }
}
