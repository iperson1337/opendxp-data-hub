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
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentType\DocumentFolderType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Exception\ClientSafeException;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\Property;
use OpenDxp\Model\Asset\Folder;
use OpenDxp\Model\Document;

class ElementPropertyType extends UnionType
{
    use ServiceTrait;

    /** @var AssetType */
    protected $assetType;

    /** @var CheckboxType */
    protected $checkboxType;

    /** @var DocumentType */
    protected $documentType;

    /** @var AssetFolderType */
    protected $assetFolderType;

    /** @var DocumentFolderType */
    protected $documentFolderType;

    /** @var ObjectFolderType */
    protected $objectFolderType;

    /** @var ObjectsType */
    protected $objectType;

    /** @var TextType */
    protected $textType;

    /** @var SelectType */
    protected $selectType;

    /**
     * @param array $config
     */
    public function __construct(Service $graphQlService, $config = [])
    {
        $this->setGraphQLService($graphQlService);
        $config['interfaces'] = [Property::getInstance()];
        parent::__construct($config);
    }

    /**
     * @throws Exception
     */
    public function getTypes(): array
    {
        $service = $this->getGraphQlService();

        $this->checkboxType = $service->getPropertyTypeDefinition('property_checkbox');
        $this->textType = $service->getPropertyTypeDefinition('property_text');
        $this->selectType = $service->getPropertyTypeDefinition('property_select');

        $supportedTypes = [
            $this->checkboxType,
            $this->textType,
            $this->selectType,
        ];

        if ($this->getGraphQlService()->querySchemaEnabled('asset')) {
            $this->assetType = $service->getPropertyTypeDefinition('property_asset');
            $supportedTypes[] = $this->assetType;
        }

        if ($this->getGraphQlService()->querySchemaEnabled('asset_folder')) {
            $this->assetFolderType = $service->getPropertyTypeDefinition('property_assetfolder');
            $supportedTypes[] = $this->assetFolderType;
        }

        if ($this->getGraphQlService()->querySchemaEnabled('object')) {
            $this->objectType = $service->getPropertyTypeDefinition('property_object');
            $supportedTypes[] = $this->objectType;
        }

        if ($this->getGraphQlService()->querySchemaEnabled('object_folder')) {
            $this->objectFolderType = $service->getPropertyTypeDefinition('property_objectfolder');
            $supportedTypes[] = $this->objectFolderType;
        }

        if ($this->getGraphQlService()->querySchemaEnabled('document')) {
            $this->documentType = $service->getPropertyTypeDefinition('property_document');
            $supportedTypes[] = $this->documentType;
        }

        if ($this->getGraphQlService()->querySchemaEnabled('document_folder')) {
            $this->documentFolderType = $service->getPropertyTypeDefinition('property_documentfolder');
            $supportedTypes[] = $this->documentFolderType;
        }

        return $supportedTypes;
    }

    public function resolveType($element, $context, ResolveInfo $info)
    {
        if ($element instanceof \OpenDxp\Model\Property) {
            $type = $element->getType();
            switch ($type) {
                case 'bool':
                case 'checkbox': {
                    return $this->checkboxType;
                }
                case 'text': {
                    return $this->textType;
                }
                case 'select': {
                    return $this->selectType;
                }
                case 'asset': {
                    $asset = $element->getData();
                    if ($asset instanceof Folder) {
                        return $this->assetFolderType;
                    } else {
                        return $this->assetType;
                    }
                }
                case 'document': {
                    $doc = $element->getData();
                    if ($doc instanceof Document\Folder) {
                        return $this->documentFolderType;
                    } else {
                        return $this->documentType;
                    }
                }
                case 'object': {
                    $object = $element->getData();
                    if ($object instanceof \OpenDxp\Model\DataObject\Folder) {
                        return $this->objectFolderType;
                    } else {
                        return $this->objectType;
                    }
                }
                default:
                    throw new ClientSafeException('unkown property type: ' . $type);
            }
        }

        return null;
    }
}
