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

use Psr\Http\Message\ServerRequestInterface;

/**
 * ResolverInterface interface
 */
interface ResolverInterface
{
    /**
     * __invoke
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function resolve(ServerRequestInterface $request): ServerRequestInterface;
}
