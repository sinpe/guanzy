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

use Psr\Http\Message\ResponseInterface;
use Sinpe\Event\Event;

/**
 * Event of application when Run end, needs EventDispatcher supporting.
 * You can do something with response, and return a response copy.
 */
class AppRunEnd extends Event
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * __construct
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Return "Response" to callee by this.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * When "Response" attachs some attributes, you can reset "Response" for callee by this.
     * 
     * @return void
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }
}
