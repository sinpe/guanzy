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
 * Rule to decide by request path whether the request should be authenticated or not.
 */
class Path implements RuleInterface
{
    /**
     * Stores all the options passed to the rule.
     */
    private $options = [
        'includes' => [],
        'excludes' => []
    ];

    /**
     * __construct
     *
     * @param array $options
     */
    public function __construct($options = [])
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
        $uri = $request->getUri()->getPath();

        $uri = preg_replace('#/+#', '/', $uri);

        // If request path is matches ignore should not authenticate.
        foreach ((array) $this->options['excludes'] as $path) {
            $path = rtrim($path, '/');
            if (!!preg_match("@^" . preg_quote($path, '@') . "(/.*)?$@", $uri)) {
                return false;
            }
        }

        // Otherwise check if path matches and we should authenticate. 
        foreach ((array) $this->options['includes'] as $path) {
            $path = rtrim($path, '/');
            if (!!preg_match("@^" . preg_quote($path, '@') . "(/.*)?$@", $uri)) {
                return true;
            }
        }

        return false;
    }
}
