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

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType\DocumentType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Fieldcollection\Definition;
use OpenDxp\Model\Document;

abstract class AbstractRelationsType extends UnionType
{
    use ServiceTrait;

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @param ClassDefinition|Definition|null $class
     * @param array $config
     */
    public function __construct(
        Service $graphQlService,
        ?Data $fieldDefinition = null,
        protected $class = null,
        $config = []
    ) {
        $this->fieldDefinition = $fieldDefinition;
        $this->setGraphQLService($graphQlService);
        $name = null;

        if ($fieldDefinition && $this->class) {
            if ($this->class instanceof ClassDefinition) {
                $name = 'object_' . $this->class->getName() . '_' . $fieldDefinition->getName();
            } elseif ($this->class instanceof Definition) {
                $name = 'fieldcollection_' . $this->class->getKey() . '_' . $fieldDefinition->getName();
            }
        }
        if ($fieldDefinition instanceof Data\AdvancedManyToManyRelation || $fieldDefinition instanceof Data\AdvancedManyToManyObjectRelation) {
            $name .= '_element';
        }

        $config['name'] = $name;
        parent::__construct($config);
    }

    /**
     * @return ClassDefinition|Definition|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $fd = $this->getFieldDefinition();

        $types = [];

        if ($fd->getObjectsAllowed()) {
            if (!$fd->getClasses()) {
                $types = array_merge($types, array_values(ClassTypeDefinitions::getAll()));
            } else {
                $classes = $fd->getClasses();
                if (!is_array($classes)) {
                    $classes = [$classes];
                }
                foreach ($classes as $className) {
                    if (is_array($className)) {
                        $className = $className['classes'];
                    }
                    if ($className === 'folder') {
                        $types[] = $this->getGraphQlService()->getDataObjectTypeDefinition('_object_folder');

                        continue;
                    }
                    $types[] = ClassTypeDefinitions::get($className);
                }
            }
        }

        if (!$fd instanceof Data\ManyToManyObjectRelation) {
            if ($fd->getAssetsAllowed()) {
                $service = $this->getGraphQlService();
                $assetType = $service->buildAssetType('asset');

                $types[] = $assetType;
            }

            if ($fd->getDocumentsAllowed()) {
                /** @var DocumentType $documentUnionType */
                $documentUnionType = $this->getGraphQlService()->getDocumentTypeDefinition('document');
                $supportedDocumentTypes = $documentUnionType->getTypes();
                $types = array_merge($types, $supportedDocumentTypes);
            }
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

    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }
}
