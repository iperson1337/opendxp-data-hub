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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator;

use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\ClassDefinition\Data;

class Table extends AbstractTable
{
    protected function getTableColumns(Data $fieldDefinition): array
    {
        $columns = [];

        if ($fieldDefinition instanceof Data\Table) {
            $numCols = (int) $fieldDefinition->getCols();
            if ($numCols === 0) {
                return [];
            }

            if ($fieldDefinition->isColumnConfigActivated()) {
                foreach ($fieldDefinition->getColumnConfig() as $columnConfig) {
                    $columns[$columnConfig['key']] = Type::string();
                }

                return $columns;
            }

            foreach (range(0, $fieldDefinition->getCols() - 1) as $i) {
                $columns['col' . $i] = Type::string();
            }
        }

        return $columns;
    }
}
