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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class Property
{
    public static $instance;

    /**
     * @return InterfaceType
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance =
                new InterfaceType(
                    [
                        'name' => 'property',
                        'fields' => [
                            'name' => [
                                'type' => Type::string(),        // name of property
                            ],
                            'type' => [
                                'type' => Type::string(),        // property type
                            ],
                        ],
                    ]

                );
        }

        return self::$instance;
    }
}
