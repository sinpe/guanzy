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

/**
 * Represents a data stream as defined in PSR-7.
 */
class FileStream extends Stream
{
    /**
     * @throws \InvalidArgumentException If argument is not a resource.
     */
    public function __construct(string $filename, string $mode = 'r')
    {
        $resource = fopen($filename, $mode);

        if (!is_resource($resource)) {
            throw new \RuntimeException(
                "could not create resource from file `$filename`"
            );
        }

        rewind($resource);

        parent::__construct($resource);
    }
}
