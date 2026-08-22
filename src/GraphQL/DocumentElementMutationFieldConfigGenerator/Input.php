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
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;

class Input extends Base
{
    /**
     * @var \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Input
     */
    public $processor;

    public function __construct(Service $graphQlService, \OpenDxp\Bundle\DataHubBundle\GraphQL\DocumentElementInputProcessor\Input $processor)
    {
        parent::__construct($graphQlService);
        $this->processor = $processor;
    }

    /**
     * @return array
     */
    public function getDocumentElementMutationFieldConfig()
    {
        return [
            'arg' => new InputObjectType(
                [
                    'name' => 'document_element_input_input',
                    'fields' => [
                        '_editableName' => Type::nonNull(Type::string()),
                        'text' => Type::string(),
                    ],
                ]
            ),
            'processor' => $this->processor->process(...),
        ];
    }
}
