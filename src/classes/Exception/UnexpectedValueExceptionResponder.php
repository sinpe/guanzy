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

use Sinpe\Framework\ArrayObject;

/**
 * Responder for this field exception.
 */
class UnexpectedValueExceptionResponder extends UnexpectedExceptionResponder
{
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

        $field = $except->getField();

        if (!empty($field)) {
            $fmt['field'] = $field;
        }

        $data = $except->getContext();

        if (!empty($data)) {
            $fmt['data'] = $data;
        }

        return new ArrayObject($fmt);
    }
}
