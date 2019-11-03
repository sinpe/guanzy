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
 * Base class for field exception.
 */
class UnexpectedValueException extends UnexpectedException
{
    /**
     * @var string
     */
    private $field;

    /**
     * __construct
     *
     * @param string $message
     * @param string $field
     * @param mixed $code
     * @param mixed $previous
     * @param array $context
     */
    public function __construct(
        string $message,
        string $field,
        $code = null,
        $previous = null,
        $context = []
    ) {
        $this->field = $field;

        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * 返回异常的字段名
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Get Responder for this exception.
     * 
     * @param ServerRequestInterface $request
     * @return Responder
     */
    public function getResponder(ServerRequestInterface $request): Responder
    {
        return new UnexpectedValueExceptionResponder($request);
    }
}
