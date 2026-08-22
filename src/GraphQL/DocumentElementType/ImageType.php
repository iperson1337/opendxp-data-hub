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
use OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType\HotspotCropType;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Document\Editable\Image;

class ImageType extends ObjectType
{
    protected static $instance;

    /**
     * @return ImageType
     *
     * @throws Exception
     */
    public static function getInstance(Service $graphQlService)
    {
        if (!self::$instance) {
            $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\HotspotType();
            $resolver->setGraphQLService($graphQlService);

            $assetType = $graphQlService->buildAssetType('asset');
            $hotspotMarkerType = $graphQlService->buildGeneralType('hotspotmarker');
            $hotspotHotspotType = $graphQlService->buildGeneralType('hotspothotspot');

            $config =
                [
                    'name' => 'document_editableImage',
                    'fields' => [
                        '_editableType' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return $value->getType();
                                }

                                return null;
                            },
                        ],
                        '_editableName' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return $value->getName();
                                }

                                return null;
                            },
                        ],
                        'image' => [
                            'type' => $assetType,
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($resolver) {
                                if ($value instanceof Image) {
                                    $data = $value->getData();
                                    if (isset($data['id'])) {
                                        $asset = Asset::getById($data['id']);
                                        if ($asset instanceof Asset) {
                                            $data = new ElementDescriptor($asset);

                                            return $resolver->resolveImage($data, $args, $context, $resolveInfo);
                                        }
                                    }
                                }

                                return null;
                            },
                        ],
                        'alt' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return $value->getAlt();
                                }

                                return null;
                            },
                        ],
                        'crop' => [
                            'type' => HotspotCropType::getInstance(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return [
                                        'cropTop' => $value->getCropTop(),
                                        'cropLeft' => $value->getCropLeft(),
                                        'cropHeight' => $value->getCropHeight(),
                                        'cropWidth' => $value->getCropWidth(),
                                        'cropPercent' => $value->getCropPercent(),
                                    ];
                                }

                                return null;
                            },
                        ],
                        'hotspots' => [
                            'type' => Type::listOf($hotspotHotspotType),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return $value->getHotspots();
                                }

                                return null;
                            },
                        ],
                        'marker' => [
                            'type' => Type::listOf($hotspotMarkerType),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Image) {
                                    return $value->getMarker();
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
