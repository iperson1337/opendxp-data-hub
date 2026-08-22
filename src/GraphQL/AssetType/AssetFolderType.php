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

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\General\FolderType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class AssetFolderType extends FolderType
{
    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = [], $context = [])
    {
        parent::__construct($graphQlService, ['name' => 'asset_folder'], $context);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $assetTree = $this->getGraphQlService()->buildGeneralType('asset_tree');
        $elementResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Element('asset', $this->getGraphQlService());

        $config['fields'] = [
            'id' => ['name' => 'id',
                'type' => Type::id(),
            ],
            'filename' => Type::string(),
            'fullpath' => [
                'type' => Type::string(),
                'args' => [
                    'thumbnail' => ['type' => Type::string()],

                ],
            ],
            'creationDate' => Type::int(),
            'modificationDate' => Type::int(),
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
                'type' => $this,
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
}
