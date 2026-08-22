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

use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\SharedType\KeyValueType;
use OpenDxp\Model\Document\PageSnippet;

class PageSnippetType extends AbstractDocumentType
{
    protected $documentElementType;

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, DocumentElementType $documentElementType, $config = ['name' => 'document_pageSnippet'], $context = [])
    {
        $this->documentElementType = $documentElementType;
        parent::__construct($graphQlService, $config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $resolver = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentResolver\PageSnippet();
        $resolver->setGraphQLService($this->getGraphQlService());

        $this->buildBaseFields($config);
        $config['fields']['elements'] = [
            'type' => Type::listOf($this->documentElementType),
            'resolve' => $resolver->resolveElements(...),
        ];

        $config['fields']['editables'] = [
            'type' => Type::listOf($this->documentElementType),
            'args' => [
                'getInheritedValues' => [
                    'type' => Type::boolean(),
                    'description' => 'Whether inherited editables should be fetched or not.',
                    'defaultValue' => false,
                ],
            ],
            'resolve' => $resolver->resolveElements(...),
        ];

        $config['fields']['title'] = [
            'type' => Type::string(),
        ];

        $config['fields']['description'] = [
            'type' => Type::string(),
        ];

        $keyValue = new ListOfType(KeyValueType::getInstance());

        $config['fields']['rendered'] = [
            'type' => Type::string(),
            'args' => [
                'attributes' => [
                    'type' => $keyValue,
                    'description' => 'Attributes passed into the controller/action',
                    'defaultValue' => [],
                ],
                'query' => [
                    'type' => $keyValue,
                    'description' => 'Query Params passed into the controller/action',
                    'defaultValue' => [],
                ],
                'options' => [
                    'type' => $keyValue,
                    'description' => 'Options passed into the controller/action',
                    'defaultValue' => [],
                ],
                'use_layout' => [
                    'type' => Type::boolean(),
                    'description' => 'Disable Layout Rendering',
                ],
            ],
            'resolve' => static function ($value, $args) {
                $documentId = $value['id'];
                $document = PageSnippet::getById($documentId);

                $attributes = KeyValueType::resolveAssociativeArray($args['attributes']);
                $query = KeyValueType::resolveAssociativeArray($args['query']);
                $options = KeyValueType::resolveAssociativeArray($args['options']);

                if ($document instanceof PageSnippet) {
                    return \OpenDxp\Model\Document\Service::render(
                        $document,
                        $attributes,
                        $args['use_layout'] ?? false,
                        $query,
                        $options
                    );
                }

                return null;
            },
        ];
    }
}
