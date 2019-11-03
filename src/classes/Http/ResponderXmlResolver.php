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

use Spatie\ArrayToXml\ArrayToXml;
use Sinpe\Framework\ArrayObject;

/**
 * Xml ContentType for common.
 */
class ResponderXmlResolver extends ResponderResolver
{
    /**
     * Handle the output for the responder.
     *
     * @param ArrayObject $output
     * @return string
     */
    public function resolve(ArrayObject $output): string
    {
        return ArrayToXml::convert($output->toArray());
    }
}
