<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter;

/**
 * 401.
 */
class BasicAuthenticatorException extends \RuntimeException
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct('Unauthorized', -401);
    }
}
