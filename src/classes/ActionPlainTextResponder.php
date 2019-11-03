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

use Psr\Http\Message\ResponseInterface;

/**
 * The throwable handler base class.
 */
class ActionPlainTextResponder extends Http\Responder
{
    /**
     * Display page content.
     *
     * @param  ResponseInterface      $response The most recent Response object
     * @param  string                 $content 
     *
     * @return ResponseInterface
     */
    public function display(string $content): ResponseInterface
    {
        return $this->handle(['data' => $content]);
    }
}
