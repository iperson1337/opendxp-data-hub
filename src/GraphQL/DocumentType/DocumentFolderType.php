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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType;

use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\General\FolderType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class DocumentFolderType extends FolderType
{
    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = [], $context = [])
    {
        parent::__construct($graphQlService, ['name' => 'document_folder'], $context);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Element('document', $this->getGraphQLService());
        $documentResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Document(new \OpenDxp\Model\Document\Service(), $this->getGraphQlService());
        $documentTree = $this->getGraphQlService()->buildGeneralType('document_tree');
        $documentTranslation = $this->getGraphQlService()->buildGeneralType('document_translation');

        {
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
                'type' => Type::string(),
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
                'parent' => [
                    'type' => $documentTree,
                    'resolve' => $resolver->resolveParent(...),
                ],
                'children' => [
                    'type' => Type::listOf($documentTree),
                    'resolve' => $resolver->resolveChildren(...),
                ],
                '_siblings' => [
                    'type' => Type::listOf($documentTree),
                    'resolve' => $resolver->resolveSiblings(...),
                ],
                'translations' => [
                    'args' => ['defaultLanguage' => ['type' => Type::string()]],
                    'type' => Type::listOf($documentTranslation),
                    'resolve' => $documentResolver->resolveTranslations(...),
                ],
            ];
        }
    }
}
