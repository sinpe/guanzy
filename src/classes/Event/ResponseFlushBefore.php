<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Event;

use Psr\Http\Message\StreamInterface;
use Sinpe\Event\Event;

/**
 * Event of application before flush, needs EventDispatcher supporting.
 */
class ResponseFlushBefore extends Event
{
    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * __construct
     *
     * @param StreamInterface $body
     */
    public function __construct(StreamInterface $body)
    {
        $this->body = $body;
    }

    /**
     * Return "Http Body" to callee by this.
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }
}
