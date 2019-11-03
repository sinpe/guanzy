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

use Psr\Http\Message\ResponseInterface;

/**
 * 
 */
abstract class ResponderResolver implements ResponderResolverInterface
{
    /**
     * Attach "Response" somme attribute and return a "Response" copy.
     * 
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function withResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
