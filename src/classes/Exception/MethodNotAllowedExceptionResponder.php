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
 * Responder for 405.
 */
class MethodNotAllowedExceptionResponder extends BadRequestExceptionResponder
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
            'text/html' => MethodNotAllowedExceptionHtmlResolver::class
        ]);

        $this->subscribeResponse(function (ResponseInterface $response) {
            $except = $this->getData('thrown');
            $response = $response->withStatus(405)
                ->withHeader('Allow', implode(', ', $except->getAllowedMethods()));
            return $response;
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
            'data' => [
                'allowed' => $except->getAllowedMethods()
            ]
        ];

        return new ArrayObject($fmt);
    }
}
