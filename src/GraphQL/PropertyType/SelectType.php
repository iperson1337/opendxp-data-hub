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

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Property;

class SelectType extends ObjectType
{
    use ServiceTrait;

    public function __construct(Service $graphQlService)
    {
        $this->graphQlService = $graphQlService;

        $config = [
            'name' => 'property_select',
            'fields' => [
                'name' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                        if ($value instanceof Property) {
                            return $value->getName();
                        }
                    },
                ],
                'type' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                        if ($value instanceof Property) {
                            return $value->getType();
                        }
                    },
                ],
                'text' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                        if ($value instanceof Property) {
                            return $value->getData();
                        }
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }
}
