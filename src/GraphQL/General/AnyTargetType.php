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
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;

class AnyTargetType extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = ['name' => 'AnyTarget'])
    {
        $this->setGraphQLService($graphQlService);

        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $service = $this->getGraphQlService();

        $types = [];

        if ($service->querySchemaEnabled('object')) {
            $objectTypes = array_values(ClassTypeDefinitions::getAll(false));
            $types = $objectTypes;
        }

        if ($service->querySchemaEnabled('asset')) {
            $assetType = $service->buildAssetType('asset');
            $types[] = $assetType;
        }

        if ($service->querySchemaEnabled('asset_folder')) {
            $assetFolderType = $service->getAssetTypeDefinition('_asset_folder');
            $types[] = $assetFolderType;
        }

        if ($service->querySchemaEnabled('document_folder') && !$service->querySchemaEnabled('document')) {
            // The 'document' union already includes _document_folder; only add it
            // separately when the document union itself is not merged below.
            $documentFolderType = $service->getDocumentTypeDefinition('_document_folder');
            $types[] = $documentFolderType;
        }

        if ($service->querySchemaEnabled('object_folder')) {
            $objectFolderType = $service->getDataObjectTypeDefinition('_object_folder');
            $types[] = $objectFolderType;
        }

        if ($service->querySchemaEnabled('document')) {
            $documentUnionType = $service->getDocumentTypeDefinition('document');
            $supportedDocumentTypes = $documentUnionType->getTypes();
            $types = array_merge($types, $supportedDocumentTypes);
        }

        return $types;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if ($element) {
            if ($element['__elementType'] == 'object') {
                if ($element['__elementSubtype'] === 'folder') {
                    return $this->getGraphQlService()->getDataObjectTypeDefinition('_object_folder');
                }

                return ClassTypeDefinitions::get($element['__elementSubtype']);
            } elseif ($element['__elementType'] == 'asset') {
                return  $this->getGraphQlService()->buildAssetType('asset');
            } elseif ($element['__elementType'] == 'document') {
                $document = Document::getById($element['id']);
                if ($document) {
                    $documentType = $document->getType();
                    $service = $this->getGraphQlService();
                    if ($documentType === 'folder') {
                        return $service->getDocumentTypeDefinition('_document_folder');
                    }

                    //TODO maybe catch unsupported types for now ?
                    $typeDefinition = $service->getDocumentTypeDefinition('document_' . $documentType);

                    return $typeDefinition;
                }
            }
        }

        return null;
    }
}
