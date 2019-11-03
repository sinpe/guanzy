<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware\RequestFilter\Rules;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\Middleware\RequestFilter\RuleInterface;

/**
 * Method rule
 */
class Method implements RuleInterface
{
    /**
     * @var array
     */
    protected $options = [
        "excludes" => ["OPTIONS"]
    ];

    /**
     * __construct
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * check
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function check(ServerRequestInterface $request): bool
    {
        return !in_array($request->getMethod(), $this->options["excludes"]);
    }
}
