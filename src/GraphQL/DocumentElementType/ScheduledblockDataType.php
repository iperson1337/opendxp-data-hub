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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;

class ScheduledblockDataType extends ObjectType
{
    use ServiceTrait;

    public function __construct(Service $graphQlService)
    {
        $this->graphQlService = $graphQlService;

        $config =
            [
                'name' => 'document_editableScheduledblock_data',
                'fields' => [
                    '_editableType' => [
                        'type' => Type::string(),
                        'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                            if ($value) {
                                return $value->getType();
                            }
                        },
                    ],
                    '_editableName' => [
                        'type' => Type::string(),
                        'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                            if ($value) {
                                return $value->getName();
                            }
                        },
                    ],
                    'key' => [
                        'type' => Type::string(),
                        'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                            if (is_array($value)) {
                                return $value['key'];
                            }

                            return null;
                        },
                    ],
                    'date' => [
                        'type' => Type::int(),
                        'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                            if (is_array($value)) {
                                return $value['date'];
                            }

                            return null;
                        },
                    ],
                ],
            ];
        parent::__construct($config);
    }
}
