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
 * RouteGroup Interface
 */
interface RouteGroupInterface
{
    /**
     * Get route pattern
     *
     * @return string
     */
    public function getOriginalPattern();

    /**
     * Execute route group callable in the context of the App
     *
     * This method invokes the route group object's callable, collecting
     * nested route objects
     */
    public function run();
}
