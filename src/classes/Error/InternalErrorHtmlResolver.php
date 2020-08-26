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

use Sinpe\Framework\ArrayObject;
use Sinpe\Framework\Http\ResponderResolver;

/**
 * The HTML resolver for 500 exception with debug details.
 */
class InternalErrorHtmlResolver extends ResponderResolver
{
    /**
     * Handle the output for the responder.
     *
     * @param ArrayObject $output
     * @return string
     */
    public function resolve(ArrayObject $output): string
    {
        $title = 'Internal Server Error';

        if (APP_DEBUG) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderHtmlError($output);

            $previous = $output->previous;
            
            foreach($previous as $i => $item)  {
                $html .= "<h2>Previous error #$i</h2>";
                $html .= $this->renderHtmlError(new ArrayObject($item));
            }
        } else {
            $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
        }

        return sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
                "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
                "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
                "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );
    }

    /**
     * Render exception or error as HTML.
     *
     * @return string
     */
    protected function renderHtmlError(ArrayObject $output)
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', $output->type);

        if ($code = $output->code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        if ($message = $output->message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        if ($file = $output->file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        if ($line = $output->line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        if ($trace = $output->trace) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }
}
