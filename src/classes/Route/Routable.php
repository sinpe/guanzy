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
 * A routable, middleware-aware object
 */
abstract class Routable
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getOriginalPattern()
    {
        return $this->pattern;
    }
}
