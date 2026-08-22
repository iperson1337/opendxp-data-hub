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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\ClassificationstoreFeatureType;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\FeatureDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\TypeInterface\CsFeature;

class QuantityValueType extends ObjectType
{
    protected static $instance = [];

    /**
     * @return mixed
     *
     * @throws Exception
     */
    public static function getInstance(Service $service, string $name, string $innerType, string $fieldname)
    {
        if (!isset(self::$instance[$name])) {
            $innerType = $service->getDataObjectTypeDefinition($innerType);

            $fields = Helper::getCommonFields();
            $fields[$fieldname] = [
                'type' => $innerType,
                'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                    if ($value instanceof FeatureDescriptor) {
                        return $value->getValue();
                    }
                },
            ];

            $config =
                [
                    'name' => $name,
                    'interfaces' => [CsFeature::getInstance()],
                    'fields' => $fields,
                ];
            self::$instance[$name] = new static($config);
        }

        return self::$instance[$name];
    }
}
