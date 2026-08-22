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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectMutationOperatorConfigGenerator;

use OpenDxp\Model\DataObject\ClassDefinition;
use Override;

class LocaleSwitcher extends Base
{
    /**
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     *
     * @return array
     */
    #[Override]
    public function getGraphQlMutationOperatorConfig($nodeDef, $class = null, $container = null, $params = [])
    {
        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\LocaleSwitcherOperator($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $factories = $this->getGraphQlService()->getDataObjectMutationTypeGeneratorFactories();

        $typeName = strtolower((string) $nodeDef['attributes']['class']);
        $factory = $factories->get('typegenerator_dataobjectmutationoperator_' . $typeName);
        $determinedType = $factory->resolveInputTypeFromNodeDef($nodeDef, $class, $container);

        return [
            'arg' => $determinedType,
            'processor' => $processor->process(...),
        ];
    }
}
