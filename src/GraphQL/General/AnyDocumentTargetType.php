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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\General;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;

class AnyDocumentTargetType extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'AnyDocumentTarget'])
    {
        $this->setGraphQLService($graphQlService);

        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $documentUnionType = $this->getGraphQlService()->getDocumentTypeDefinition('document');

        return $documentUnionType->getTypes();
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if ($element) {
            if ($element['__elementType'] == 'document') {
                $document = Document::getById($element['id']);
                if ($document) {
                    $documentType = $document->getType();
                    $service = $this->getGraphQlService();
                    if ($documentType === 'folder') {
                        return $service->getDocumentTypeDefinition('_document_folder');
                    }

                    $typeDefinition = $service->getDocumentTypeDefinition('document_' . $documentType);

                    return $typeDefinition;
                }
            } else {
                die('To be done');
            }
        }

        return null;
    }
}
