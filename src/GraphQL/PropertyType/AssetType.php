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
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\Element\Data\MarkerHotspotItem;
use OpenDxp\Model\Property;

class AssetType extends ObjectType
{
    use ServiceTrait;

    /**
     * @throws Exception
     */
    public function __construct(Service $graphQlService)
    {
        $this->graphQlService = $graphQlService;
        $assetType = $graphQlService->buildAssetType('asset');

        $config = [
            'name' => 'property_asset',
            'fields' => [
                'name' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                        if ($value instanceof MarkerHotspotItem || $value instanceof Property) {
                            return $value->getName();
                        }
                    },
                ],
                'type' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                        if ($value instanceof MarkerHotspotItem || $value instanceof Property) {
                            return $value->getType();
                        }
                    },
                ],
                'asset' => [
                    'type' => $assetType,
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($graphQlService) {
                        if ($value instanceof MarkerHotspotItem || $value instanceof Property) {
                            if ($value instanceof MarkerHotspotItem) {
                                $element = \OpenDxp\Model\Element\Service::getElementById($value->getType(), $value->getValue());
                            } else {
                                $element = $value->getData();
                            }

                            if ($element) {
                                if (!WorkspaceHelper::checkPermission($element, 'read')) {
                                    return null;
                                }

                                $data = new ElementDescriptor($element);
                                $graphQlService->extractData($data, $element, $args, $context, $resolveInfo);

                                return $data;
                            }
                        }

                        return null;
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }
}
