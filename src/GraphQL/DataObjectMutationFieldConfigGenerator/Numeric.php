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

use GraphQL\Type\Definition\Type;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use Override;

class Numeric extends Base
{
    /** {@inheritdoc } */
    #[Override]
    public function getGraphQlMutationFieldConfig($nodeDef, $class, $container = null, $params = [])
    {
        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\Base($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $type = Type::float();
        $nodeAttributes = $nodeDef['attributes'];
        $key = $nodeAttributes['attribute'];
        $fieldDefinition = $this->getGraphQlService()->getObjectFieldHelper()->getFieldDefinitionFromKey($class, $key);
        if ($fieldDefinition instanceof Data\Numeric) {
            if ($fieldDefinition->getInteger()) {
                $type = Type::int();
            }
        }

        return [
            'arg' => $type,
            'processor' => $processor->process(...),
        ];
    }
}
