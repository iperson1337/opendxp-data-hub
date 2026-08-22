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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectMutationFieldConfigGenerator;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use Override;

class Table extends Base
{
    /** {@inheritdoc } */
    #[Override]
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container = null, $params = [])
    {
        $fieldName = $nodeDef['attributes']['attribute'];
        $tableDef = $this->getGraphQlService()->getObjectFieldHelper()->getFieldDefinitionFromKey($class, $fieldName);
        $inputItems = [];
        $numCols = 0;

        if ($tableDef instanceof Data\Table) {
            $numCols = (int) $tableDef->getCols();
        }

        $this->getProcessors($processors, $tableDef);

        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\Table($nodeDef, $processors);
        $processor->setGraphQLService($this->getGraphQlService());

        foreach (range(0, $numCols - 1) as $i) {
            $inputItems['col' . $i] = Type::string();
        }

        $rowInput = new InputObjectType([
            'name' => 'RowInput',
            'fields' => $inputItems,
        ]);

        $inputType = new InputObjectType([
            'name' => 'TableInput',
            'fields' => [
                'replace' => [
                    'type' => Type::boolean(),
                    'description' => 'if true then the entire table will be overwritten',
                ],
                'rows' => [
                    'type' => Type::listOf($rowInput),
                ],
            ],
        ]);

        return [
            'arg' => $inputType,
            'processor' => $processor->process(...),
        ];
    }

    public function getProcessors(&$processors, $tableDef)
    {
        $tableHeaderStr = $tableDef->getData();
        $tableHeader = [];

        if (strlen((string) $tableHeaderStr) > 0) {
            $tableHeader = explode('|', (string) $tableHeaderStr);
        }

        $processors = ['tableHeader' => $tableHeader];
    }
}
