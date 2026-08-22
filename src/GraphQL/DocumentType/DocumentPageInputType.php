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

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class DocumentPageInputType extends InputObjectType
{
    use ServiceTrait;

    protected $processors = [];

    /**
     * @param array $config
     * @param array $context
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'document_page_input'], $context = [])
    {
        $this->setGraphQLService($graphQlService);
        $this->build($config);
        parent::__construct($config);
    }

    /**
     * @param array $config
     */
    public function build(&$config)
    {
        $service = $this->getGraphQlService();

        $elementTypes = $service->getSupportedDocumentElementMutationDataTypes();
        $elementFields = [];
        $processors = [];
        foreach ($elementTypes as $elementType) {
            $typedef = $service->buildDocumentElementDataMutationType($elementType);
            $elementFields[$elementType] = Type::listOf($typedef['arg']);
            $processors[$elementType] = $typedef['processor'];
        }

        $this->processors = $processors;

        $elementInputTypeList = new InputObjectType([ //TODO this is document_page specific
            'name' => 'document_pagemutationelements',
            'fields' => $elementFields,
        ]);

        $config['fields'] = [
            'module' => Type::string(),
            'controller' => Type::string(),
            'action' => Type::string(),
            'template' => Type::string(),
            'elements' => $elementInputTypeList,
        ];
    }

    /**
     * @return array
     */
    public function getProcessors()
    {
        return $this->processors;
    }
}
