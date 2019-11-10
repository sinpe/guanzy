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

/**
 * InternalErrorLogger class.
 */
class InternalErrorLogger
{
    /**
     * Write to the error log
     *
     * @return void
     */
    public static function write(\Throwable $error)
    {
        $message = 'Internal Server Error:' . PHP_EOL;

        $message .= self::ex2text($error);

        while ($error = $error->getPrevious()) {
            $message .= PHP_EOL . 'previous error:' . PHP_EOL;
            $message .= self::ex2text($error);
        }

        $message .= PHP_EOL . 'view in rendered output by enabling the "debug" setting.' . PHP_EOL;

        error_log($message);
    }

    /**
     * Error to text.
     *
     * @param \Throwable $error
     * @return string
     */
    private static function ex2text(\Throwable $error): string
    {
        $text = sprintf('type: %s' . PHP_EOL, get_class($error));

        if ($code = $error->getCode()) {
            $text .= sprintf('code: %s' . PHP_EOL, $code);
        }

        if ($message = $error->getMessage()) {
            $text .= sprintf('message: %s' . PHP_EOL, htmlentities($message));
        }

        if ($file = $error->getFile()) {
            $text .= sprintf('file: %s' . PHP_EOL, $file);
        }

        if ($line = $error->getLine()) {
            $text .= sprintf('line: %s' . PHP_EOL, $line);
        }

        if ($trace = $error->getTraceAsString()) {
            $text .= sprintf('trace: %s', $trace);
        }

        return $text;
    }
}
