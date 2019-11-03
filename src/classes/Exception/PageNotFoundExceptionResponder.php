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
use Psr\Http\Message\ResponseInterface;
use Sinpe\Framework\ArrayObject;

/**
 * Responder for 404.
 */
class PageNotFoundExceptionResponder extends BadRequestExceptionResponder
{
    /**
     * __construct
     * 
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);

        $this->registerResolvers([
            'text/html' => PageNotFoundExceptionHtmlResolver::class
        ]);

        $this->subscribeResponse(function (ResponseInterface $response) {
            return $response->withStatus(404);
        });
    }

    /**
     * Format the data for resolver.
     *
     * @return ArrayObject
     */
    protected function fmtData(): ArrayObject
    {
        $except = $this->getData('thrown');

        $fmt = [
            'code' => $except->getCode(),
            'message' => $except->getMessage(),
            'data' => $except->getContext()
        ];

        return new ArrayObject($fmt);
    }
}
