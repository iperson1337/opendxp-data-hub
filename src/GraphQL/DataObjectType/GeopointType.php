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

class GeopointType extends ObjectType
{
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Geopoint();
        if (!self::$instance) {
            $config =
                [
                    'fields' => [
                        'longitude' => [
                            'type' => Type::float(),
                            'resolve' => $resolver->resolveLongitude(...),
                        ],
                        'latitude' => [
                            'type' => Type::float(),
                            'resolve' => $resolver->resolveLatitude(...),
                        ],

                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
