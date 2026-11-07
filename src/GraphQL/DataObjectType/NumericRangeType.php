<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\Data\NumericRange;

/**
 * Value of the core `numericRange` data type, in the shape of {@see NumericRange::toArray()}.
 */
class NumericRangeType extends ObjectType
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static([
                'name' => 'NumericRange',
                'fields' => [
                    'minimum' => [
                        'type' => Type::float(),
                        'resolve' => static fn ($value) => $value instanceof NumericRange ? $value->getMinimum() : null,
                    ],
                    'maximum' => [
                        'type' => Type::float(),
                        'resolve' => static fn ($value) => $value instanceof NumericRange ? $value->getMaximum() : null,
                    ],
                ],
            ]);
        }

        return self::$instance;
    }
}
