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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\AssetType;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\Element;

class AssetType extends ObjectType
{
    use ServiceTrait;

    /**
     * @var string
     */
    protected $fieldname;

    /**
     * @param array $config
     * @param array $context
     *
     * @throws Exception
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'asset'], $context = [])
    {
        $config['interfaces'] = [Element::getInstance()];
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     *
     * @throws Exception
     */
    public function build(&$config)
    {
        $resolver = new Resolver\AssetType();
        $resolver->setGraphQLService($this->getGraphQlService());

        $service = $this->getGraphQlService();
        $assetTree = $service->buildGeneralType('asset_tree');
        $assetMetadataItemType = $service->buildAssetType('asset_metadataitem');
        $assetEmbeddedMetaInfoItemType = $service->buildAssetType('asset_embeddedmetainfoitem');
        $elementTagType = $service->buildGeneralType('element_tag');

        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $elementResolver = new Resolver\Element('asset', $this->getGraphQlService());

        $dimensionsType = new ObjectType([
            'name' => 'dimensions',
            'fields' => [
                'width' => Type::int(),
                'height' => Type::int(),
            ],
        ]);
        $focalPointsType = new ObjectType([
            'name' => 'focalPoints',
            'fields' => [
                'x' => Type::float(),
                'y' => Type::float(),
            ],
        ]);

        // see https://developer.mozilla.org/en-US/docs/Learn/HTML/Multimedia_and_embedding/Responsive_images#Resolution_switching_Same_size_different_resolutions
        $resolutionsType = Type::listOf(new ObjectType([
            'name' => 'resolutions',
            'fields' => [
                'url' => Type::string(),
                'resolution' => Type::float(),
            ],
        ]));
        $resolutionsArgumentsType = [
            'type' => Type::listOf(Type::float()),
            'description' => 'List of resolution types [2, 5, ...]',
            'defaultValue' => [2],
        ];

        $config['fields'] = [
            'creationDate' => Type::int(),
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
            ],
            'filename' => Type::string(),
            'fullpath' => [
                'type' => Type::string(),
                'args' => [
                    'thumbnail' => ['type' => Type::string()],
                    'format' => ['type' => Type::string()],
                    'deferred' => ['type' => Type::boolean(), 'defaultValue' => false],
                ],
                'resolve' => $resolver->resolvePath(...),
            ],
            'resolutions' => [
                'type' => $resolutionsType,
                'args' => [
                    'thumbnail' => ['type' => Type::nonNull(Type::string())],
                    'format' => ['type' => Type::string()],
                    'types' => $resolutionsArgumentsType,
                ],
                'resolve' => $resolver->resolveResolutions(...),
            ],
            'dimensions' => [
                'type' => $dimensionsType,
                'args' => [
                    'thumbnail' => ['type' => Type::string()],
                    'format' => ['type' => Type::string()],
                ],
                'resolve' => $resolver->resolveDimensions(...),
            ],
            'duration' => [
                'type' => Type::float(),
                'resolve' => $resolver->resolveDuration(...),
            ],
            'focalPoints' => [
                'type' => $focalPointsType,
                'resolve' => $resolver->resolveFocalPoints(...),
            ],
            'srcset' => [
                'type' => Type::listOf(new ObjectType([
                    'name' => 'srcset',
                    'fields' => [
                        'descriptor' => Type::string(),
                        'url' => Type::string(),
                        'resolutions' => [
                            'type' => $resolutionsType,
                            'args' => [
                                'types' => $resolutionsArgumentsType,
                            ],
                            'resolve' => $resolver->resolveResolutions(...),
                        ],
                    ],
                ])),
                'args' => [
                    'thumbnail' => ['type' => Type::nonNull(Type::string())],
                    'format' => ['type' => Type::string()],
                    'deferred' => ['type' => Type::boolean(), 'defaultValue' => false],
                ],
                'resolve' => $resolver->resolveSrcSet(...),
            ],
            'mimetype' => Type::string(),
            'modificationDate' => Type::int(),
            'type' => Type::string(),
            'filesize' => Type::int(),
            'version' => [
                'type' => Type::int(),
                'resolve' => $resolver->resolveVersion(...),
            ],
            'data' => [
                'type' => Type::string(),
                'args' => [
                    'thumbnail' => ['type' => Type::string()],
                    'format' => ['type' => Type::string()],
                ],
                'resolve' => $resolver->resolveData(...),
            ],
            'tags' => [
                'type' => Type::listOf($elementTagType),
                'args' => [
                    'name' => ['type' => Type::string()],
                ],
                'resolve' => $resolver->resolveTag(...),
            ],
            'metadata' => [
                'type' => Type::listOf($assetMetadataItemType),
                'args' => [
                    'language' => ['type' => Type::string()],
                    'ignore_language' => ['type' => Type::boolean()],
                ],
                'resolve' => $resolver->resolveMetadata(...),
            ],
            'embeddedMetaInfo' => [
                'type' => Type::listOf($assetEmbeddedMetaInfoItemType),
                'resolve' => $resolver->resolveEmbeddedMetaInfo(...),
            ],
            'properties' => [
                'type' => Type::listOf($propertyType),
                'args' => [
                    'keys' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'List of property key names to include '
                            . '(if omitted, all properties are returned).',
                    ],
                ],
                'resolve' => $elementResolver->resolveProperties(...),
            ],
            'parent' => [
                'type' => $assetTree,
                'resolve' => $elementResolver->resolveParent(...),
            ],
            'children' => [
                'type' => Type::listOf($assetTree),
                'resolve' => $elementResolver->resolveChildren(...),
            ],
            '_siblings' => [
                'type' => Type::listOf($assetTree),
                'resolve' => $elementResolver->resolveSiblings(...),
            ],
        ];
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): void
    {
        $this->fieldname = $fieldname;
    }
}
