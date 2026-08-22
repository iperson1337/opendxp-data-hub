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

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Model\Document\Editable\Relations;

class RelationsType extends ObjectType
{
    /** @var static|null */
    protected static $instance;

    /**
     * @return static
     *
     * @throws Exception
     */
    public static function getInstance(Service $graphQlService)
    {
        if (!self::$instance) {
            $anyTargetType = $graphQlService->buildGeneralType('anytarget');

            $config =
                [
                    'name' => 'document_editableRelations',
                    'fields' => [
                        '_editableType' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relations) {
                                    return $value->getType();
                                }
                            },
                        ],
                        '_editableName' => [
                            'type' => Type::string(),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) {
                                if ($value instanceof Relations) {
                                    return $value->getName();
                                }
                            },
                        ],
                        'relations' => [
                            'type' => Type::listOf($anyTargetType),
                            'resolve' => static function ($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null) use ($graphQlService) {
                                if ($value instanceof Relations) {
                                    $targets = $value->getElements();
                                    if ($targets) {
                                        $result = [];
                                        foreach ($targets as $target) {
                                            $data = new ElementDescriptor($target);
                                            $graphQlService->extractData($data, $target, $args, $context, $resolveInfo);
                                            $result[] = $data;
                                        }

                                        return $result;
                                    }
                                }
                            },
                        ],
                    ],
                ];
            self::$instance = new static($config);
        }

        return self::$instance;
    }
}
