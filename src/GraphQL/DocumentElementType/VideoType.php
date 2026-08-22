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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\AssetType\AssetType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\RelationHelper;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\Document\Editable\Video;

class VideoType extends ObjectType
{
    protected static $instance;

    /**
     * @return static
     */
    public static function getInstance(Service $graphQlService, AssetType $assetType)
    {
        if (!self::$instance) {
            $config =
                [
                    'name' => 'document_editableVideo',
                    'fields' => [
                        '_editableType' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value) {
                                    return $value->getType();
                                }
                            },
                        ],
                        '_editableName' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value) {
                                    return $value->getName();
                                }
                            },
                        ],
                        'id' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Video) {
                                    return $value->getId();
                                }
                            },
                        ],
                        'type' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value) {
                                    return $value->getVideoType();
                                }
                            },
                        ],
                        'title' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Video) {
                                    return $value->getTitle();
                                }
                            },
                        ],
                        'description' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Video) {
                                    return $value->getDescription();
                                }
                            },
                        ],
                        'posterAsset' => [
                            'type' => $assetType,
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($graphQlService) {
                                if ($value instanceof Video) {
                                    $relation = $value->getPosterAsset();
                                    if ($relation) {
                                        $data = RelationHelper::processRelation($relation, $graphQlService, $args, $context, $resolveInfo);

                                        return $data;
                                    }
                                }

                                return null;
                            },
                        ],
                        'videoAsset' => [
                            'type' => $assetType,
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($graphQlService) {
                                if ($value instanceof Video) {
                                    $relation = $value->getVideoAsset();
                                    if ($relation) {
                                        $data = RelationHelper::processRelation($relation, $graphQlService, $args, $context, $resolveInfo);

                                        return $data;
                                    }
                                }

                                return null;
                            },
                        ],
                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
