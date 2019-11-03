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

use Sinpe\Framework\ArrayObject;
use Sinpe\Framework\Http\ResponderResolver;

/**
 * The HTML resolver for 404 exception.
 */
class PageNotFoundExceptionHtmlResolver extends ResponderResolver
{
    /**
     * Handle the output for the responder.
     *
     * @param ArrayObject $output
     * @return string
     */
    public function resolve(ArrayObject $output): string
    {
        return <<<END
<html>
    <head>
        <title>{$output->message}</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
            strong{
                display:inline-block;
                width:65px;
            }
        </style>
    </head>
    <body>
        <h1>{$output->message}</h1>
        <p>
            The page you are looking for could not be found. Check the address bar
            to ensure your URL is spelled correctly. If all else fails, you can
            visit our home page at the link below.
        </p>
        <a href='{$output->data['home']}'>Visit the Home Page</a>
    </body>
</html>
END;
    }
}
