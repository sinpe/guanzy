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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines a contract for invoking a route callable.
 */
interface StrategyInterface
{
    /**
     * Invoke a route callable.
     *
     * @param callable               $callable The callable to invoke using the strategy.
     *
     * @return ResponseInterface|string The response from the callable.
     */
    public function process(callable $callable): ResponseInterface;
}
