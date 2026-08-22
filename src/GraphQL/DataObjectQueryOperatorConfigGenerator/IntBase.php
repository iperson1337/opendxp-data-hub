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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryOperatorConfigGenerator;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\ClassDefinition;
use Override;

class IntBase extends Base
{
    /**
     * @param string $typeName
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     * @param array $params

     *
     * @return ScalarType|Type
     */
    #[Override]
    public function getGraphQlType($typeName, $nodeDef, $class = null, $container = null, $params = [])
    {
        return Type::int();
    }

    /**
     * @param array $attributes
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return \GraphQL\Type\Definition\ScalarType
     */
    #[Override]
    public function getFieldType($attributes, $class = null, $container = null)
    {
        return Type::int();
    }
}
