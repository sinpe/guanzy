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

/**
 * Exception for 401.
 */
class UnauthorizedBasicException extends UnauthorizedException
{
    /**
     * __construct
     */
    public function __construct($realm)
    {
        parent::__construct('Unauthorized', -401, ['realm' => $realm]);
    }
}
