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

class StructuredTable extends AbstractTable
{
    protected function getTableColumns(Data $fieldDefinition): array
    {
        $cols = [];
        if ($fieldDefinition instanceof Data\StructuredTable) {
            foreach ($fieldDefinition->getCols() as $i => $columnConfig) {
                $key = $columnConfig['key'] ?? 'col' . $i;

                $type = match ($columnConfig['type']) {
                    'number' => Type::float(),
                    'bool' => Type::boolean(),
                    default => Type::string(),
                };

                $cols[$key] = $type;
            }
        }

        return $cols;
    }
}
