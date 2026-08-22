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

class LinkType extends ObjectType
{
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Link();
            $config =
                [
                    'fields' => [
                        'text' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveText(...),
                        ],
                        'path' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolvePath(...),
                        ],
                        'target' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveTarget(...),
                        ],
                        'anchor' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveAnchor(...),
                        ],
                        'title' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveTitle(...),
                        ],
                        'accesskey' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveAccesskey(...),
                        ],
                        'rel' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveRel(...),
                        ],
                        'class' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveClass(...),
                        ],
                        'attributes' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveAttributes(...),
                        ],
                        'tabindex' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveTabindex(...),
                        ],
                        'parameters' => [
                            'type' => Type::string(),
                            'resolve' => $resolver->resolveParameters(...),
                        ],
                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
