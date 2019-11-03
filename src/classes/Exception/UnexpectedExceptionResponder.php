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

use Psr\Http\Message\ResponseInterface;
use Sinpe\Framework\ArrayObject;
use Sinpe\Framework\Http\Responder;

/**
 * Responder for user message.
 */
class UnexpectedExceptionResponder extends Responder
{
    /**
     * Invoke the handler
     *
     * @param \Throwable $data
     * @return ResponseInterface
     */
    public function handle(\Throwable $error): ResponseInterface
    {
        return parent::handle(['thrown' => $error]);
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
            'message' => $except->getMessage()
        ];

        $data = $except->getContext();

        if (!empty($data)) {
            $fmt['data'] = $data;
        }

        return new ArrayObject($fmt);
    }
}
