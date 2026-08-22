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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\Element;

abstract class AbstractDocumentType extends ObjectType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = [])
    {
        $config['interfaces'] = [Element::getInstance()];
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    abstract public function build(&$config);

    /**
     * @param array $config
     */
    public function buildBaseFields(&$config)
    {
        $propertyType = $this->getGraphQlService()->buildGeneralType('element_property');
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Element('document', $this->getGraphQLService());
        $documentResolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\Resolver\Document(new \OpenDxp\Model\Document\Service(), $this->getGraphQlService());
        $documentTree = $this->getGraphQlService()->buildGeneralType('document_tree');
        $elementTagType = $this->getGraphQlService()->buildGeneralType('element_tag');
        $documentTranslation = $this->getGraphQlService()->buildGeneralType('document_translation');

        $config['fields'] = [
            'creationDate' => Type::int(),
            'id' => ['name' => 'id',
                'type' => Type::id(),
            ],
            'fullpath' => [
                'type' => Type::string()],
            'modificationDate' => Type::int(),
            'published' => ['name' => 'published',
                'type' => Type::boolean(),
            ],
            'type' => Type::string(),
            'controller' => Type::string(),
            'action' => Type::string(),
            'template' => Type::string(),
            'tags' => [
                'type' => Type::listOf($elementTagType),
                'args' => [
                    'name' => ['type' => Type::string()],
                ],
                'resolve' => $resolver->resolveTag(...),
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
