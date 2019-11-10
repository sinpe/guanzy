<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Sinpe\Framework\Exception\BadRequestException;
use Sinpe\Framework\Exception\InternalException;

/**
 * CSRF protection middleware.
 */
class CSRF
{
    /**
     * Prefix for CSRF parameters (omit trailing "_" underscore)
     *
     * @var string
     */
    protected $prefix;

    /**
     * CSRF storage
     *
     * Should be either an array or an object. If an object is used, then it must
     * implement \ArrayAccess and should implement \Countable and Iterator (or
     * IteratorAggregate) if storage limit enforcement is required.
     *
     * @var array|\ArrayAccess
     */
    protected $storage;

    /**
     * Number of elements to store in the storage array
     *
     * Default is 200, set via constructor
     *
     * @var integer
     */
    protected $storageLimit;

    /**
     * CSRF Strength
     *
     * @var int
     */
    protected $strength;

    /**
     * Callable to be executed if the CSRF validation fails
     *
     * Signature of callable is:
     *    function($request, $response, $next)
     * and a $response must be returned.
     *
     * @var callable
     */
    protected $failureCallable;

    /**
     * Determines whether or not we should persist the token throughout the duration of the user's session.
     *
     * For security, Slim-Csrf will *always* reset the token if there is a validation error.
     * @var bool True to use the same token throughout the session (unless there is a validation error),
     * false to get a new token with each request.
     */
    protected $persistentTokenMode;

    /**
     * Stores the latest key-pair generated by the class
     *
     * @var array
     */
    protected $keyPair;

    /**
     * Create new CSRF guard
     *
     * @param string                 $prefix
     * @param null|array|\ArrayAccess $storage
     * @param null|callable          $failureCallable
     * @param integer                $storageLimit
     * @param integer                $strength
     * @param boolean                $persistentTokenMode
     * @throws \RuntimeException if the session cannot be found
     */
    public function __construct(
        $prefix = 'csrf',
        &$storage = null,
        callable $failureCallable = null,
        $storageLimit = 200,
        $strength = 16,
        $persistentTokenMode = false
    ) {
        $this->prefix = rtrim($prefix, '_');

        if ($strength < 16) {
            throw new BadRequestException('CSRF middleware failed. Minimum strength is 16.');
        }

        $this->strength = $strength;
        $this->storage = &$storage;

        $this->setFailureCallable($failureCallable);
        $this->setStorageLimit($storageLimit);
        $this->setPersistentTokenMode($persistentTokenMode);

        $this->keyPair = null;
    }

    /**
     * Retrieve token name key
     *
     * @return string
     */
    public function getTokenNameKey()
    {
        return $this->prefix . '_name';
    }

    /**
     * Retrieve token value key
     *
     * @return string
     */
    public function getTokenValueKey()
    {
        return $this->prefix . '_value';
    }

    /**
     * Invoke middleware
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->validateStorage();
        // Validate POST, PUT, DELETE, PATCH requests
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            // 
            $body = $request->getParsedBody();
            $body = $body ? (array) $body : [];

            $name = isset($body[$this->getTokenNameKey()]) ? $body[$this->getTokenNameKey()] : false;
            $value = isset($body[$this->getTokenValueKey()]) ? $body[$this->getTokenValueKey()] : false;

            if (!$name || !$value || !$this->validateToken($name, $value)) {
                throw new BadRequestException('CSRF failed');
            }
        }

        // Generate new CSRF token if persistentTokenMode is false, or if a valid keyPair has not yet been stored
        if (!$this->persistentTokenMode || !$this->loadLastKeyPair()) {
            $request = $this->generateNewToken($request);
        } elseif ($this->persistentTokenMode) {
            $pair = $this->loadLastKeyPair() ? $this->keyPair : $this->generateToken();
            $request = $this->attachRequestAttributes($request, $pair);
        }

        // Enforce the storage limit
        $this->enforceStorageLimit();

        return $handler->handle($request);
    }

    /**
     * @param $prefix
     * @param $storage
     * @return mixed
     */
    public function validateStorage()
    {
        if (is_array($this->storage)) {
            return $this->storage;
        }

        if ($this->storage instanceof \ArrayAccess) {
            return $this->storage;
        }

        if (!isset($_SESSION)) {
            throw new InternalException('CSRF failed, Session not found.');
        }

        if (!array_key_exists($this->prefix, $_SESSION)) {
            $_SESSION[$this->prefix] = [];
        }

        $this->storage = &$_SESSION[$this->prefix];

        return $this->storage;
    }

    /**
     * Generates a new CSRF token
     *
     * @return array
     */
    public function generateToken()
    {
        // Generate new CSRF token
        $name = uniqid($this->prefix);
        $value = $this->createToken();
        $this->saveToStorage($name, $value);

        $this->keyPair = [
            $this->prefix . '_name' => $name,
            $this->prefix . '_value' => $value
        ];

        return $this->keyPair;
    }

    /**
     * Generates a new CSRF token and attaches it to the Request Object
     * 
     * @param  ServerRequestInterface $request PSR7 response object.
     * 
     * @return ServerRequestInterface PSR7 response object.
     */
    public function generateNewToken(ServerRequestInterface $request)
    {
        $pair = $this->generateToken();

        $request = $this->attachRequestAttributes($request, $pair);

        return $request;
    }

    /**
     * Validate CSRF token from current request
     * against token value stored in $_SESSION
     *
     * @param  string $name  CSRF name
     * @param  string $value CSRF token value
     *
     * @return bool
     */
    public function validateToken($name, $value)
    {
        $token = $this->getFromStorage($name);

        if (function_exists('hash_equals')) {
            $result = ($token !== false && hash_equals($token, $value));
        } else {
            $result = ($token !== false && $token === $value);
        }

        // If we're not in persistent token mode, delete the token.
        if (!$this->persistentTokenMode) {
            $this->removeFromStorage($name);
        }

        return $result;
    }

    /**
     * Create CSRF token value
     *
     * @return string
     */
    protected function createToken()
    {
        return bin2hex(random_bytes($this->strength));
    }

    /**
     * Save token to storage
     *
     * @param  string $name  CSRF token name
     * @param  string $value CSRF token value
     */
    protected function saveToStorage($name, $value)
    {
        $this->storage[$name] = $value;
    }

    /**
     * Get token from storage
     *
     * @param  string      $name CSRF token name
     *
     * @return string|bool CSRF token value or `false` if not present
     */
    protected function getFromStorage($name)
    {
        return isset($this->storage[$name]) ? $this->storage[$name] : false;
    }

    /**
     * Get the most recent key pair from storage.
     *
     * @return string[]|null Array containing name and value if found, null otherwise
     */
    protected function getLastKeyPair()
    {
        // Use count, since empty \ArrayAccess objects can still return false for `empty`
        if (count($this->storage) < 1) {
            return null;
        }

        foreach ($this->storage as $name => $value) {
            continue;
        }

        $keyPair = [
            $this->prefix . '_name' => $name,
            $this->prefix . '_value' => $value
        ];

        return $keyPair;
    }

    /**
     * Load the most recent key pair in storage.
     *
     * @return bool `true` if there was a key pair to load in storage, false otherwise.
     */
    protected function loadLastKeyPair()
    {
        $this->keyPair = $this->getLastKeyPair();

        if ($this->keyPair) {
            return true;
        }

        return false;
    }

    /**
     * Remove token from storage
     *
     * @param  string $name CSRF token name
     */
    protected function removeFromStorage($name)
    {
        $this->storage[$name] = ' ';
        unset($this->storage[$name]);
    }

    /**
     * Remove the oldest tokens from the storage array so that there
     * are never more than storageLimit tokens in the array.
     *
     * This is required as a token is generated every request and so
     * most will never be used.
     */
    protected function enforceStorageLimit()
    {
        if ($this->storageLimit < 1) {
            return;
        }

        // $storage must be an array or implement \Countable and \Traversable
        if (
            !is_array($this->storage)
            && !($this->storage instanceof \Countable && $this->storage instanceof \Traversable)
        ) {
            return;
        }

        if (is_array($this->storage)) {
            while (count($this->storage) > $this->storageLimit) {
                array_shift($this->storage);
            }
        } else {
            // array_shift() doesn't work for \ArrayAccess, so we need an iterator in order to use rewind()
            // and key(), so that we can then unset
            $iterator = $this->storage;
            if ($this->storage instanceof \IteratorAggregate) {
                $iterator = $this->storage->getIterator();
            }
            while (count($this->storage) > $this->storageLimit) {
                $iterator->rewind();
                unset($this->storage[$iterator->key()]);
            }
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param $pair
     * @return static
     */
    protected function attachRequestAttributes(ServerRequestInterface $request, $pair)
    {
        return $request->withAttribute($this->prefix . '_name', $pair[$this->prefix . '_name'])
            ->withAttribute($this->prefix . '_value', $pair[$this->prefix . '_value']);
    }

    /**
     * Setter for failureCallable
     *
     * @param mixed $failureCallable Value to set
     * @return $this
     */
    public function setFailureCallable($failureCallable)
    {
        $this->failureCallable = $failureCallable;
        return $this;
    }

    /**
     * Setter for persistentTokenMode
     *
     * @param bool $persistentTokenMode True to use the same token throughout the session (unless there is a validation error),
     * false to get a new token with each request.
     * @return $this
     */
    public function setPersistentTokenMode($persistentTokenMode)
    {
        $this->persistentTokenMode = $persistentTokenMode;
        return $this;
    }

    /**
     * Setter for storageLimit
     *
     * @param integer $storageLimit Value to set
     * @return $this
     */
    public function setStorageLimit($storageLimit)
    {
        $this->storageLimit = (int) $storageLimit;
        return $this;
    }

    /**
     * Getter for persistentTokenMode
     *
     * @return bool
     */
    public function getPersistentTokenMode()
    {
        return $this->persistentTokenMode;
    }

    /**
     * @return string
     */
    public function getTokenName()
    {
        return isset($this->keyPair[$this->getTokenNameKey()]) ? $this->keyPair[$this->getTokenNameKey()] : null;
    }

    /**
     * @return string
     */
    public function getTokenValue()
    {
        return isset($this->keyPair[$this->getTokenValueKey()]) ? $this->keyPair[$this->getTokenValueKey()] : null;
    }
}
