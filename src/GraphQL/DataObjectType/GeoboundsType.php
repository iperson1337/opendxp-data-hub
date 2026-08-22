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

class GeoboundsType extends ObjectType
{
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Geobounds();
        if (!self::$instance) {
            $config =
                [
                    'fields' => [
                        'northEast' => [
                            'type' => GeopointType::getInstance(),
                            'resolve' => $resolver->resolveNorthEast(...),
                        ],
                        'southWest' => [
                            'type' => GeopointType::getInstance(),
                            'resolve' => $resolver->resolveSouthWest(...),
                        ],

                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
