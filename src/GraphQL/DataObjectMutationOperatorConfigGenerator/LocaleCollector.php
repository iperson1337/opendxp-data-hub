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

use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\LocaleCollectorOperator;
use OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType\LocalizedType;
use Override;

class LocaleCollector extends Base
{
    /**
     * @param array $nodeDef
     * @param \OpenDxp\Model\DataObject\ClassDefinition|null $class
     * @param object|null $container
     * @param array $params
     *
     * @return array
     */
    #[Override]
    public function getGraphQlMutationOperatorConfig($nodeDef, $class = null, $container = null, $params = [])
    {
        $processor = new LocaleCollectorOperator($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $factories = $this->getGraphQlService()->getDataObjectMutationTypeGeneratorFactories();

        $typeName = strtolower((string) $nodeDef['attributes']['class']);
        $factory = $factories->get('typegenerator_dataobjectmutationoperator_' . $typeName);
        $determinedType = LocalizedType::getInstance(
            $factory->resolveInputTypeFromNodeDef($nodeDef, $class, $container)
        );

        return [
            'arg' => $determinedType,
            'processor' => $processor->process(...),
        ];
    }
}
