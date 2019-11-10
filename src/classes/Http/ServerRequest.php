<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Http;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

use Sinpe\Framework\ArrayObject;

/**
 * This class represents an HTTP request. It manages
 * the request method, URI, headers, cookies, and body
 * according to the PSR-7 standard.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/MessageInterface.php
 * @link https://github.com/php-fig/http-message/blob/master/src/ServerRequestInterface.php
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * The request query string params
     *
     * @var array
     */
    protected $queryParams;

    /**
     * The request cookies
     *
     * @var array
     */
    protected $cookies;

    /**
     * The server environment variables at the time the request was created.
     *
     * @var array
     */
    protected $serverParams;

    /**
     * List of uploaded files
     *
     * @var UploadedFileInterface[]
     */
    protected $uploadedFiles;

    /**
     * The request attributes (route segment names and values)
     *
     * @var ArrayObject
     */
    protected $attributes;

    /**
     * List of request body parsers (e.g., url-encoded, JSON, XML, multipart)
     *
     * @var callable[]
     */
    protected $bodyParsers = [];

    /**
     * The request body parsed (if possible) into a PHP array or object
     *
     * @var null|array|object
     */
    protected $bodyParsed = false;

    /**
     * The route arguments.
     *
     * @var array
     */
    protected $routeArguments = [];

    /**
     * Create new HTTP request.
     *
     * Adds a host header when none was provided and a host is defined in uri.
     *
     * @param string           $method        The request method
     * @param UriInterface     $uri           The request URI object
     * @param array            $serverParams  The server environment variables
     */
    public function __construct(
        string $method,
        $uri,
        array $serverParams
    ) {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        if (!isset($headers['Authorization'])) {
            if (isset($serverParams['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $serverParams['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($serverParams['PHP_AUTH_USER'])) {
                $pw = isset($serverParams['PHP_AUTH_PW']) ? $serverParams['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($serverParams['PHP_AUTH_USER'] . ':' . $pw);
            } elseif (isset($serverParams['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $serverParams['PHP_AUTH_DIGEST'];
            }
        }

        parent::__construct($method, $uri, new Headers($headers), new FileStream('php://input', 'r'));

        $this->serverParams = $serverParams;
        $this->cookies = Cookies::parseHeader($this->headers->get('Cookie', []));

        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }

        $this->registerMediaTypeParser('application/json', function ($input) {
            $result = json_decode($input, true);
            if (!is_array($result)) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });

        if (
            $this->isPost() &&
            in_array(
                $this->getMediaType(),
                ['application/x-www-form-urlencoded', 'multipart/form-data']
            )
        ) {
            // parsed body must be $_POST
            $this->bodyParsed = $_POST;
        }

        $this->attributes = new ArrayObject();
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve a server parameter.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getServerParam($key, $default = null)
    {
        $serverParams = $this->getServerParams();

        return isset($serverParams[$key]) ? $serverParams[$key] : $default;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Fetch cookie value from cookies sent by the client to the server.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key     The attribute name.
     * @param mixed  $default Default value to return if the attribute does not exist.
     *
     * @return mixed
     */
    public function getCookieParam($key, $default = null)
    {
        $cookies = $this->getCookieParams();
        $result = $default;
        if (isset($cookies[$key])) {
            $result = $cookies[$key];
        }

        return $result;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies = $cookies;
        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        if (is_array($this->queryParams)) {
            return $this->queryParams;
        }

        if ($this->uri === null) {
            return [];
        }

        parse_str($this->uri->getQuery(), $this->queryParams); // <-- URL decodes data

        return $this->queryParams;
    }

    /**
     * Fetch parameter value from query string.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getQueryParam($key, $default = null)
    {
        $gotParams = $this->getQueryParams();
        $result = $default;
        if (isset($gotParams[$key])) {
            $result = $gotParams[$key];
        }

        return $result;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        if (!$this->uploadedFiles) {
            //
            $parsed = [];

            foreach ($_FILES as $field => $uploadedFile) {

                $parsed[$field] = [];
                // one
                if (!is_array($uploadedFile['error'])) {
                    /*
                    array (
                        'field' => 
                            array (
                                'name' => 'file.jpg',
                                'type' => 'image/jpeg',
                                'tmp_name' => 'E:\\wamp64\\tmp\\phpF461.tmp',
                                'error' => 0,
                                'size' => 20928,
                            ),
                        )
                    */
                    $parsed[$field] = new UploadedFile(
                        $uploadedFile['tmp_name'],
                        isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
                        $uploadedFile['error'],
                        isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
                        mime_content_type($uploadedFile['tmp_name'])
                        //isset($uploadedFile['type']) ? $uploadedFile['type'] : null,
                    );
                } else {
                    /*
                    array (
                        'field' => 
                            array (
                                'name' => 
                                    array (
                                        0 => 'face.jpg',
                                        1 => 'face.jpg',
                                    ),
                                'type' => 
                                    array (
                                        0 => 'image/jpeg',
                                        1 => 'image/jpeg',
                                    ),
                                'tmp_name' => 
                                    array (
                                        0 => 'E:\\wamp64\\tmp\\php78E0.tmp',
                                        1 => 'E:\\wamp64\\tmp\\php78E1.tmp',
                                    ),
                                'error' => 
                                    array (
                                        0 => 0,
                                        1 => 0,
                                    ),
                                'size' => 
                                    array (
                                        0 => 20928,
                                        1 => 20928,
                                    ),
                            ),
                        )
                    */

                    $subArray = [];
                    foreach ($uploadedFile['error'] as $fileIdx => $error) {
                        //
                        $subArray[$fileIdx] = new UploadedFile(
                            $uploadedFile['tmp_name'][$fileIdx],
                            $uploadedFile['size'][$fileIdx],
                            $uploadedFile['error'][$fileIdx],
                            $uploadedFile['name'][$fileIdx],
                            mime_content_type($uploadedFile['tmp_name'][$fileIdx])
                        );
                    }

                    $parsed[$field] = $subArray;
                }
            }

            $this->uploadedFiles = $parsed;
        }

        return $this->uploadedFiles ?? [];
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     * @throws \RuntimeException if the request body media type parser returns an invalid value
     */
    public function getParsedBody()
    {
        if ($this->bodyParsed !== false) {
            return $this->bodyParsed;
        }

        if (!$this->body) {
            return null;
        }

        $mediaType = $this->getMediaType();

        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts) - 1];
        }

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $body = (string) $this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new \RuntimeException(
                    i18n('request body media type parser return value must be an array, an object, or null')
                );
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }

        return null;
    }

    /**
     * Fetch parameter value from request body.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new \InvalidArgumentException(i18n('parsed body value must be an array, an object, or null'));
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    /**
     * Register media type parser.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param string   $mediaType A HTTP media type (excluding content-type
     *     params).
     * @param callable $callable  A callable that returns parsed contents for
     *     media type.
     */
    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof \Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string) $mediaType] = $callable;
    }

    /**
     * Force Body to be parsed again.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return $this
     */
    public function reparseBody()
    {
        $this->bodyParsed = false;

        return $this;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    /**
     * Create a new instance with the specified derived request attributes.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * This method allows setting all new derived request attributes as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attributes.
     *
     * @param  array $attributes New attributes
     * @return static
     */
    public function withAttributes(array $attributes)
    {
        $clone = clone $this;
        $clone->attributes = new ArrayObject($attributes);

        return $clone;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
    }

    /**
     * Fetch associative array of body and query string parameters.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param array|null $only list the keys to retrieve.
     * @return array|null
     */
    public function getParams(array $only = null)
    {
        $params = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        if ($postParams) {
            $params = array_merge($params, (array) $postParams);
        }

        if ($only) {
            $onlyParams = [];
            foreach ($only as $key) {
                if (array_key_exists($key, $params)) {
                    $onlyParams[$key] = $params[$key];
                }
            }
            return $onlyParams;
        }

        return $params;
    }

    /**
     * Fetch request parameter value from body or query string (in that order).
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string $key The parameter key.
     * @param  string $default The default value.
     *
     * @return mixed The parameter value.
     */
    public function getParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $gotParams = $this->getQueryParams();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($gotParams[$key])) {
            $result = $gotParams[$key];
        }

        return $result;
    }

    /**
     * Is this a GET request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this an XHR request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get request content type.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request content type, if known
     */
    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }

    /**
     * Get request media type, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null The request media type, minus content-type params
     */
    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    /**
     * Get request media type params, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return array
     */
    public function getMediaTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Get request content character set, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null
     */
    public function getContentCharset()
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get request content length, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return int|null
     */
    public function getContentLength()
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int) $result[0] : null;
    }

    /**
     * setRouteArguments
     *
     * @param array $routeArguments
     * @return void
     */
    public function setRouteArguments(array $routeArguments)
    {
        $this->routeArguments = $routeArguments ?? [];
    }

    /**
     * getRouteArguments
     *
     * @param string $item
     * @param mixed $default
     * @return mixed
     */
    public function getRouteArguments(string $item = null, $default = null)
    {
        if (!empty($item)) {
            if (isset($this->routeArguments[$item])) {
                return $this->routeArguments[$item];
            } else {
                return $default;
            }
        }
        return $this->routeArguments;
    }

    /**
     * This method is applied to the cloned object
     * after PHP performs an initial shallow-copy. This
     * method completes a deep-copy by creating new objects
     * for the cloned object's internal reference pointers.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body = clone $this->body;
    }
}
