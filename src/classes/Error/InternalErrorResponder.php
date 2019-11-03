<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Error;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sinpe\Framework\ArrayObject;
use Sinpe\Framework\Http\Responder;

/**
 * Responder for uncaught.
 */
class InternalErrorResponder extends Responder
{
    /**
     * @var string
     */
    private $acceptType;

    /**
     * __construct
     * 
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct($request);

        $this->registerResolvers([
            'text/html' => InternalErrorHtmlResolver::class
        ]);

        $this->subscribeResponse(function (ResponseInterface $response) {
            // if not debug, with log trace. 
            if (!APP_DEBUG) {
                InternalErrorLogger::write($this->getData('thrown'));
            }
            $this->acceptType = $response->getHeaderLine('Content-Type');
            $response = $response->withStatus(500);
            return $response;
        });
    }

    /**
     * Invoke the handler
     *
     * @param \Throwable $data
     * @return ResponseInterface
     */
    public function handle(\Throwable $error): ResponseInterface
    {
        return parent::handle(['thrown' => $error]);
    }

    /**
     * Format the data for resolver.
     *
     * @return ArrayObject
     */
    protected function fmtData(): ArrayObject
    {
        $error = $this->getData('thrown');

        $fmt = [
            'code' => min($error->getCode(), -1),
            'message' => 'Error'
        ];

        // 
        if (APP_DEBUG) {
            $fmt['type'] = get_class($error);
            $fmt['message'] = $this->wrapCdata($error->getMessage());
            $fmt['file'] = $error->getFile();
            $fmt['line'] = $error->getLine();
            $fmt['trace'] = $this->wrapCdata($error->getTraceAsString());

            while ($error = $error->getPrevious()) {
                $fmt['previous'][] = [
                    'type' => get_class($error),
                    'code' => $error->getCode(),
                    'message' => $this->wrapCdata($error->getMessage()),
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'trace' => $this->wrapCdata($error->getTraceAsString())
                ];
            }
        }

        return new ArrayObject($fmt);
    }

    /**
     * Returns a CDATA section with the given content.
     *
     * @param  string $content
     * @return string
     */
    private function wrapCdata(string $content): string
    {
        if (in_array($this->acceptType, [
            'application/xml',
            'text/xml'
        ])) {
            return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
        } else {
            return $content;
        }
    }
}
