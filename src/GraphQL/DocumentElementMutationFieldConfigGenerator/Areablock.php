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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementMutationFieldConfigGenerator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\AreablockDataInputType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation\MutationType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Areablock extends Base
{
    /** @var InputObjectType|null */
    public static $itemType;

    /** @var AreablockDataInputType */
    protected $areablockDataInputType;

    /** @var \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Areablock */
    protected $processor;

    public function __construct(
        Service $graphQlService,
        AreablockDataInputType $areablockDataInputType,
        \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Areablock $processor
    ) {
        parent::__construct($graphQlService);

        $this->areablockDataInputType = $areablockDataInputType;
        $this->processor = $processor;
    }

    /**
     * @return array
     */
    public function getDocumentElementMutationFieldConfig()
    {
        if (!self::$itemType) {
            self::$itemType = new InputObjectType(
                [
                    'name' => 'document_element_input_areablock_item',
                    'fields' => fn () => [
                        'type' => Type::nonNull(Type::string()),
                        'hidden' => Type::boolean(),
                        'replace' => [
                            'type' => Type::boolean(),
                            'description' => 'if true (default), all elements inside the block will be replaced',
                        ],
                        'editables' => MutationType::$documentElementTypes,
                    ],
                ]
            );
        }

        return [
            'arg' => new InputObjectType(
                [
                    'name' => 'document_element_input_areablock',
                    'fields' => fn () => [
                        '_editableName' => Type::nonNull(Type::string()),
                        'indices' => Type::listOf($this->areablockDataInputType),
                        'items' => [
                            'type' => Type::listOf(self::$itemType),
                        ],
                    ],
                ]

            ),
            'processor' => $this->processor->process(...),
        ];
    }
}
