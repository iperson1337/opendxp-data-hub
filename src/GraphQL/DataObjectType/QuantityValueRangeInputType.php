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

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Same unit lookup as {@see QuantityValueInputType}: `unitId` wins over `unit` (abbreviation).
 */
class QuantityValueRangeInputType extends InputObjectType
{
    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static([
                'name' => 'QuantityValueRangeInput',
                'fields' => [
                    'minimum' => Type::float(),
                    'maximum' => Type::float(),
                    'unit' => Type::string(),
                    'unitId' => Type::string(),
                ],
            ]);
        }

        return self::$instance;
    }
}
