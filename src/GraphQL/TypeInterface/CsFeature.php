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

class CsFeature
{
    public static $instance;

    /**
     * Defines fields common to all query types
     *
     * @return InterfaceType
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance =
                new InterfaceType(
                    [
                        'name' => 'csFeatureInterface',
                        'fields' => [
                            'id' => [
                                'type' => Type::int(),
                            ],
                            'name' => [
                                'type' => Type::string(),
                            ],
                            'title' => [
                                'type' => Type::string(),
                            ],
                            'type' => [
                                'type' => Type::string(),
                            ],
                            'description' => [
                                'type' => Type::string(),
                            ],
                        ],
                    ]

                );
        }

        return self::$instance;
    }
}
