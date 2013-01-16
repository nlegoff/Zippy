<?php

/*
 * This file is part of Zippy.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Zippy\Parser;

use Alchemy\Zippy\Exception\InvalidArgumentException;
use Alchemy\Zippy\Parser\GNUTarOutputParser;
use Alchemy\Zippy\Parser\ParserInterface;

class ParserFactory
{
    /**
     * Maps the corresponding parser to the selected adapter
     *
     * @param   $adapterName An adapter name
     *
     * @return ParserInterface
     *
     * @throws InvalidArgumentException In case no parser were found
     */
    public static function create($adapterName)
    {
        switch ($adapterName) {
            case 'gnu-tar':
                return new GNUTarOutputParser($adapterName);
                break;

            default:
                throw InvalidArgumentException();
                break;
        }
    }
}