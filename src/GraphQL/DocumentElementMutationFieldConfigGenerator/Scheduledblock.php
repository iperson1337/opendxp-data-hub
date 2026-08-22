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
use OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementType\ScheduledblockDataInputType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Mutation\MutationType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Scheduledblock extends Base
{
    /** @var InputObjectType|null */
    public static $itemType;

    /** @var ScheduledblockDataInputType */
    protected $scheduledblockDataInputType;

    /** @var \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Scheduledblock */
    protected $processor;

    public function __construct(
        Service $graphQlService,
        ScheduledblockDataInputType $scheduledblockDataInputType,
        \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Scheduledblock $processor
    ) {
        parent::__construct($graphQlService);

        $this->scheduledblockDataInputType = $scheduledblockDataInputType;
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
                    'name' => 'document_element_input_scheduledblock_item',
                    'fields' => fn () => [
                        'date' => Type::int(),
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
                    'name' => 'document_element_input_scheduledblock',
                    'fields' => fn () => [
                        '_editableName' => Type::nonNull(Type::string()),
                        'indices' => Type::listOf($this->scheduledblockDataInputType),
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
