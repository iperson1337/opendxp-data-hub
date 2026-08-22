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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\PropertyType;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Document;

class ObjectsType extends UnionType
{
    use ServiceTrait;

    public function __construct(Service $graphQlService)
    {
        $this->setGraphQLService($graphQlService);

        // @phpstan-ignore-next-line - We can't define the types in the constructor because the `getTypes` method is overwritten
        parent::__construct(['name' => 'hotspot_metadata_object']);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $types = [];

        $service = $this->getGraphQlService();

        if ($service->querySchemaEnabled('object')) {
            $objectTypes = array_values(ClassTypeDefinitions::getAll(true));
            $types = array_merge($types, $objectTypes);
        }

        if ($service->querySchemaEnabled('object_folder')) {
            $types[] = $this->getGraphQlService()->getDataObjectTypeDefinition('_object_folder');
        }

        if ($service->querySchemaEnabled('document')) {
            $documentUnionType = $this->getGraphQlService()->getDocumentTypeDefinition('document');
            $supportedDocumentTypes = $documentUnionType->getTypes();
            $types = array_merge($types, $supportedDocumentTypes);
        }

        if ($service->querySchemaEnabled('asset')) {
            $types[] = $this->getGraphQlService()->buildAssetType('asset');
        }

        if ($service->querySchemaEnabled('asset_folder')) {
            $types[] = $this->getGraphQlService()->getAssetTypeDefinition('_asset_folder');
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

                    $typeDefinition = $service->getDocumentTypeDefinition('document_' . $documentType);

                    return $typeDefinition;
                }
            }
        }

        return null;
    }
}
