<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Route;

/**
 * Resolves a callable.
 */
interface CallableResolverInterface
{
    /**
     * Invoke the resolved callable.
     *
     * @param mixed $toResolve
     *
     * @return callable
     */
    public function resolve($toResolve);
}
