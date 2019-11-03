<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\Http\Responder;

/**
 * Exception for 405.
 */
class MethodNotAllowedException extends BadRequestException
{
    /**
     * HTTP methods allowed
     *
     * @var array
     */
    protected $allowedMethods = [];

    /**
     * __construct
     *
     * @param array $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods ?? [];
        parent::__construct('Method not allowed', -405);
    }

    /**
     * Get allowed methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods ?? [];
    }

    /**
     * Get Responder for this exception.
     * 
     * @param ServerRequestInterface $request
     * @return Responder
     */
    public function getResponder(ServerRequestInterface $request): Responder
    {
        return new MethodNotAllowedExceptionResponder($request);
    }
}
