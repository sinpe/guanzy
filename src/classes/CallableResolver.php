<?php

namespace Sinpe\Framework;

use Sinpe\Framework\Route\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
final class CallableResolver implements CallableResolverInterface
{
    /**
     * pattern，<class>:<method> or <class with __invoke>
     */
    const CALLABLE_PATTERN = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    /**
     * Resolve toResolve into a closure so that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @param mixed $toResolve
     * @return callable
     *
     * @throws \RuntimeException if the callable does not exist
     * @throws \RuntimeException if the callable is not resolvable
     */
    public function resolve($toResolve)
    {
        // __invoke instance or Closure
        if (is_callable($toResolve)) {
            return $toResolve;
        }

        if (!is_string($toResolve)) {
            $this->assertCallable($toResolve);
        }

        // check for callable as "class:method".
        // this is "Controller" pattern.
        if (preg_match(static::CALLABLE_PATTERN, $toResolve, $matches)) {
            $resolved = $this->resolveCallable($matches[1], $matches[2]);
        } else {
            // this is "Action" pattern (__invoke)
            $resolved = $this->resolveCallable($toResolve);
        }

        $this->assertCallable($resolved);

        return $resolved;
    }

    /**
     * Check if string is something in the DIC
     * that's callable or is a class name which has an __invoke() method.
     *
     * @param string $class
     * @param string $method
     * @return callable
     *
     * @throws \RuntimeException if the callable does not exist
     */
    protected function resolveCallable($class, $method = '__invoke')
    {
        if (!class_exists($class)) {
            throw new \RuntimeException(i18n('callable %s does not exist', $class));
        }

        return [container($class), $method];
    }

    /**
     * @param Callable $callable
     *
     * @throws \RuntimeException if the callable is not resolvable
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new \RuntimeException(i18n(
                '%s is not resolvable',
                is_array($callable) || is_object($callable) ? json_encode($callable) : $callable
            ));
        }
    }
}
