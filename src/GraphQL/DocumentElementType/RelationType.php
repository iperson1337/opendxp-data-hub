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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\Document\Editable\Relation;

class RelationType extends ObjectType
{
    protected static $instance;

    /**
     * @return RelationType
     *
     * @throws Exception
     */
    public static function getInstance(Service $graphQlService)
    {
        if (!self::$instance) {
            $anyTargetType = $graphQlService->buildGeneralType('anytarget');

            $config =
                [
                    'name' => 'document_editableRelation',
                    'fields' => [
                        '_editableType' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relation) {
                                    return $value->getType();
                                }
                            },
                        ],
                        '_editableName' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relation) {
                                    return $value->getName();
                                }
                            },
                        ],
                        'id' => [
                            'type' => Type::int(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relation) {
                                    return $value->getId();
                                }
                            },
                        ],
                        'type' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relation) {
                                    return $value->getType();
                                }
                            },
                        ],
                        'subtype' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relation) {
                                    return $value->getSubtype();
                                }
                            },
                        ],
                        'relation' => [
                            'type' => $anyTargetType,
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($graphQlService) {
                                if ($value instanceof Relation) {
                                    $target = $value->getElement();
                                    if ($target) {
                                        $desc = new ElementDescriptor($target);
                                        $graphQlService->extractData($desc, $target, $args, $context, $resolveInfo);

                                        return $desc;
                                    }
                                }
                            },
                        ],
                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
