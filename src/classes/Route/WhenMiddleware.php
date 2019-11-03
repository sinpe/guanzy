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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

abstract class WhenMiddleware implements MiddlewareInterface
{
    /**
     * @var \Closure
     */
    private $resolver;

    /**
     * 设置条件检查器
     *
     * @param callable $callable
     */
    public function when(\Closure $callable)
    {
        $this->resolver = $callable;

        return $this;
    }

    /**
     * 条件决断
     */
    public function assert(ServerRequestInterface $request)
    {
        if (!$this->resolver) {
            throw new \RuntimeException(i18n('When resolver not exists.'));
        }

        // 条件决断失败
        if (call_user_func($this->resolver, $request) === false) {
            return false;
        }

        return $this;
    }

}
