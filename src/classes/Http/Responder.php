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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sinpe\Framework\ArrayObject;

/**
 * A responder base class.
 */
class Responder
{

    /**
     * Result of action
     *
     * @var ArrayObject
     */
    private $data;

    /**
     * Subscribe for response.
     *
     * @var callable
     */
    private $subscribe;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * Known handled content types
     *
     * @var array
     */
    protected $resolvers = [
        'application/json' => ResponderJsonResolver::class,
        'text/html' => ResponderHtmlResolver::class,
        'application/xml' => ResponderXmlResolver::class,
        'text/xml' => ResponderXmlResolver::class
    ];

    /**
     * __construct
     * 
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Invoke the handler
     *
     * @param array $data
     * @return ResponseInterface
     */
    public function handle($data=null): ResponseInterface
    {
        if ($data) {
            if (!is_array($data)) {
                throw new \Exception(i18n('a array needed'));
            }
            $this->data = new ArrayObject($data);
        }

        $response = $this->genResponse($this->request);

        if (is_callable($this->subscribe)) {
            $response = call_user_func($this->subscribe, $response);
            if (!$response instanceof ResponseInterface) {
                throw new \Exception(i18n('subscribe MUST return a %s.', ResponseInterface::class));
            }
        }

        return $response;
    }

    /**
     * @return mixed
     */
    final protected function getData(string $item = null)
    {
        $data = $this->data ?? new ArrayObject;

        if (!empty($item) && $data->has($item)) {
            return $data[$item];
        }

        return $data;
    }

    /**
     * 生成Response
     *
     * @return ResponseInterface
     */
    protected function genResponse(ServerRequestInterface $request): ResponseInterface
    {
        $acceptType = $request->getHeaderLine('Accept');
        //
        $supportTypes = array_keys($this->resolvers);

        $selectedContentTypes = array_intersect(explode(',', $acceptType), $supportTypes);

        if (count($selectedContentTypes)) {
            $contentType = current($selectedContentTypes);
        } else {
            // handle +json and +xml specially
            if (preg_match('/\+(json|xml)/', $acceptType, $matches)) {
                //
                $mediaType = 'application/' . $matches[1];
                if (in_array($mediaType, $supportTypes)) {
                    $contentType = $mediaType;
                }
            }
        }

        if (empty($contentType)) {
            $contentType = 'text/html';
        }

        $response = new Response(200);

        $response = $response->withHeader('Content-Type', "{$contentType};charset=utf-8");

        if (array_key_exists($contentType, $this->resolvers)) {

            $resolver = $this->resolvers[$contentType];

            if ($resolver instanceof \Closure) {
                /*
                resolver需要依赖时，注入通过responder引入，再用此方式调用resolver
                比如：依赖Setting
                function ($response) use($setting) {
                    $resolver = new $resolver($setting);
                    return $resolver->resolve(new ArrayObject($content));
                }
                */
                $content = $resolver($this->fmtData());
            } else {
                // Resolver can be overrided with container
                $resolver = container($resolver);
                $response = $resolver->withResponse($response);
                $content = $resolver->resolve($this->fmtData());
            }
            //
        } else {
            throw new \UnexpectedValueException(i18n('can not render unknown content type "%s"', $contentType));
        }

        $body = new EmptyStream('rwb+');
        $body->write($content ?? '');

        return $response->withBody($body);
    }

    /**
     * Attach "Response" somme attribute and return a "Response" copy,
     * You can override me in your class.
     *
     * @param callable $callback
     * @return static
     */
    final public function subscribeResponse(callable $callback): Responder
    {
        $this->subscribe = $callback;
        return $this;
    }

    /**
     * Register resolver
     *
     * @return static
     */
    protected function registerResolvers(array $resolvers)
    {
        $this->resolvers = array_merge($this->resolvers, $resolvers);
        return $this;
    }

    /**
     * Format the data for resolver.
     *
     * @return ArrayObject
     */
    protected function fmtData(): ArrayObject
    {
        return $this->getData();
    }
}
