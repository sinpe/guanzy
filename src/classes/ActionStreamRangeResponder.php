<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 断点续传
 */
class ActionStreamRangeResponder extends Http\Responder
{
    /**
     * Create response
     *
     * @return ResponseInterface
     */
    protected function genResponse(ServerRequestInterface $request): ResponseInterface
    {
        $file = $this->getData('file');
        $fileExists = $this->getData('file_exists');

        if (!call_user_func($fileExists, $file)) {
            throw new Exception\PageNotFoundException([
                'home' => (string) $this->getRequest()->getUri()->withPath('')->withQuery('')->withFragment('')
            ]);
        }
        // 
        $size = filesize($file);
        $baseName = basename($file);

        $response = new Http\Response(200);

        $httpRange = $request->getServerParam('HTTP_RANGE');

        if ($httpRange) {
            $response = $response->withStatus(206);
            list($name, $range) = explode('=', $httpRange);
            list($begin, $end) = explode('-', $range);
            if ($end == 0) {
                $end = $size - 1;
            }
        } else {
            $begin = 0;
            $end = $size - 1;
        }

        $acceptType = $request->getHeaderLine('Accept');

        $contentType = $acceptType ?: 'application/octet-stream';

        $response = $response->withHeader('Content-Type', "{$contentType};charset=utf-8")
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Length', ($end - $begin + 1))
            ->withHeader('Content-Range', "bytes {$begin}-{$end}/{$size}")
            ->withHeader('Content-Disposition', 'attachment; filename=' . $baseName);

        $resource = fopen($file, 'r');

        return $response->withBody(new Http\Stream($file));
    }
}
