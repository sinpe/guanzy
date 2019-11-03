<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\Http\Responder;

/**
 * Exception for 404.
 */
class PageNotFoundException extends BadRequestException
{
    /**
     * __construct
     */
    public function __construct(array $context)
    {
        parent::__construct('Page not found', -404, $context);
    }

    /**
     * Get Responder for this exception.
     * 
     * @param ServerRequestInterface $request
     * @return Responder
     */
    public function getResponder(ServerRequestInterface $request): Responder
    {
        return new PageNotFoundExceptionResponder($request);
    }
}
