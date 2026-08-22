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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\Helper;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\BaseDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

class AssetBase
{
    use ServiceTrait;

    /**
     * @param string $attribute
     * @param Data $fieldDefinition
     * @param ClassDefinition $class
     */
    public function __construct(
        Service $graphQlService,
        public $attribute,
        public $fieldDefinition,
        public $class
    ) {
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param BaseDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return ElementDescriptor|null
     *
     * @throws Exception
     */
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $asset = Service::resolveValue($value, $this->fieldDefinition, $this->attribute, $args = []);

        if (!$asset) {
            return null;
        }

        $assetElement = $this->getAssetElement($asset);

        if (!WorkspaceHelper::checkPermission($assetElement, 'read')) {
            return null;
        }

        $data = new ElementDescriptor($assetElement);
        $this->getGraphQlService()->extractData($data, $assetElement, $args, $context, $resolveInfo);
        if (isset($data['data'])) {
            $data['data'] = base64_encode((string) $data['data']);
        }

        return $data;
    }

    /**
     * Return the actual asset (AbstractElement)
     *
     * @param mixed $asset
     *
     * @return mixed
     */
    public function getAssetElement($asset)
    {
        return $asset;
    }
}
