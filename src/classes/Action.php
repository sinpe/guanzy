<?php
/*
 * This file is part of long/guanzy.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action
 */
class Action
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

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
     * Return "ServerRequest"
     *
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Dsiplay a page content.
     *
     * @param  string $content 
     * @return ResponseInterface
     */
    protected function display(string $content): ResponseInterface
    {
        return $this->getResponder()->display($content);
    }

    /**
     * Output some datas (differ from a page)
     *
     * @param mixed $data
     * @param integer $code
     * @return ResponseInterface
     */
    protected function output($data, $code = 0): ResponseInterface
    {
        return $this->getResponder()->handle([
            'code' => $code ?? 0,
            'data' => $data
        ]);
    }

    /**
     * Output a message with context (differ from a page)
     *
     * @param string $message
     * @param integer $code
     * @param mixed $data
     * @return ResponseInterface
     */
    protected function message(string $message, $code = 0, $data = null): ResponseInterface
    {
        $output = [
            'code' => $code ?? 0,
            'message' => $message,
        ];

        if (!empty($data)) {
            $output['data'] = $data;
        }

        return $this->getResponder()->handle($output);
    }

    /**
     * Success with context (differ from a page)
     *
     * @param string $message
     * @param integer $code
     * @param mixed $data
     * @return ResponseInterface
     */
    protected function success(string $message, $code = 0, $data = null): ResponseInterface
    {
        if ($code < 0) {
            throw new \RuntimeException(i18n('normal code must be greater than or equal to 0'));
        }

        return $this->message($message, $code, $data);
    }

    /**
     * alias
     */
    protected function succee(string $message, $code = 0, $data = null): ResponseInterface
    {
        return $this->success($message, $code, $data);
    }

    /**
     * Error with context (differ from a page)
     *
     * @param string $message
     * @param integer $code
     * @param mixed $data
     * @return ResponseInterface
     */
    protected function error(string $message, $code = -1, $data = null): ResponseInterface
    {
        if ($code >= 0) {
            throw new \RuntimeException(i18n('error code must be less than 0'));
        }

        return $this->message($message, $code, $data);
    }

    /**
     * alias
     */
    protected function fail(string $message, $code = -1, $data = null): ResponseInterface
    {
        return $this->error($message, $code, $data);
    }

    /**
     * Download a file.
     *
     * @param string $file
     * @param callable $fileExists a callback for detecting file existence.
     * @return ResponseInterface
     */
    protected function download(string $file, callable $fileExists = null): ResponseInterface
    {
        return $this->getStreamResponder()->handle([
            'file' => $file,
            'file_exists' => is_callable($fileExists) ? $fileExists : function ($file) {
                return file_exists($file);
            }
        ]);
    }

    /**
     * Create a text responder.
     * 
     * @return Http\Responder
     */
    protected function getResponder(): Http\Responder
    {
        return new ActionPlainTextResponder($this->request);
    }

    /**
     * Create a stream responder.
     * 
     * @return Http\Responder
     */
    protected function getStreamResponder(): Http\Responder
    {
        return new ActionStreamCommonResponder($this->request);
    }
}
