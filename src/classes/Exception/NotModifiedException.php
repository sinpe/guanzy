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
 * Exception for 304.
 */
class NotModifiedException extends UnexpectedException
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct('Not Modified', -304);
    }

    /**
     * Get Responder for this exception.
     * 
     * @param ServerRequestInterface $request
     * @return Responder
     */
    public function getResponder(ServerRequestInterface $request): Responder
    {
        return new NotModifiedExceptionResponder($request);
    }
}
