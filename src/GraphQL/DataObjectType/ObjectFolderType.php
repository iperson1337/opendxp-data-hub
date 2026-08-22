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

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\General\FolderType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class ObjectFolderType extends FolderType
{
    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = [], $context = [])
    {
        parent::__construct($graphQlService, ['name' => 'object_folder'], $context);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $objectTreeType = $this->getGraphQlService()->buildGeneralType('object_tree');

        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\DataObject($this->getGraphQLService());

        $config['fields'] = [
            'id' => [
                'name' => 'id',
                'type' => Type::id(),
            ],
            'key' => Type::string(),
            'fullpath' => [
                'type' => Type::string(),
            ],
            'creationDate' => Type::int(),
            'modificationDate' => Type::int(),
            'parent' => [
                'type' => $objectTreeType,
                'resolve' => $resolver->resolveParent(...),
            ],
            'index' => [
                'type' => Type::int(),
                'resolve' => $resolver->resolveIndex(...),
            ],
            'childrenSortBy' => [
                'type' => Type::string(),
                'resolve' => $resolver->resolveChildrenSortBy(...),
            ],
            'children' => [
                'type' => Type::listOf($objectTreeType),
                'args' => [
                    'objectTypes' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'list of object types (object, variant, folder)',
                    ],
                ],
                'resolve' => $resolver->resolveChildren(...),
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
                'resolve' => $resolver->resolveProperties(...),
            ],
            '_siblings' => [
                'type' => Type::listOf($objectTreeType),
                'args' => [
                    'objectTypes' => [
                        'type' => Type::listOf(Type::string()),
                        'description' => 'list of object types (object, variant, folder)',
                    ],
                ],
                'resolve' => $resolver->resolveSiblings(...),
            ],
        ];
    }
}
