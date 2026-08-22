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

use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;

abstract class Base
{
    use ServiceTrait;

    public function __construct(Service $graphQlService)
    {
        $this->graphQlService = $graphQlService;
    }

    /**
     * @param array $nodeDef
     *
     * @return mixed
     */
    public function resolveInputTypeFromNodeDef($nodeDef, ClassDefinition $class)
    {
        $nodeDefAttributes = $nodeDef['attributes'];
        $children = $nodeDefAttributes['children'];

        $firstChild = $children[0];
        $firstChildAttributes = $firstChild['attributes'];
        $service = $this->getGraphQlService();

        $factories = $service->getDataObjectMutationTypeGeneratorFactories();

        if ($firstChild['isOperator']) {
            //  we only support the simple case with one child
            $operatorClass = $firstChildAttributes['class'];
            $typeName = strtolower($operatorClass);
            $mutationConfigGenerator = $factories->get('typegenerator_dataobjectmutationoperator_' . $typeName);
            $result = $mutationConfigGenerator->resolveInputTypeFromNodeDef($firstChild, $class);
        } else {
            $typeName = $firstChildAttributes['dataType'];
            $mutationConfigGenerator = $factories->get('typegenerator_dataobjectmutationdatatype_' . $typeName);
            $config = $mutationConfigGenerator->getGraphQlMutationFieldConfig($firstChild, $class);
            $result = $config['arg'];
        }

        return $result;
    }

    /**
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     * @param array $params
     *
     * @return array
     */
    public function getGraphQlMutationOperatorConfig($nodeDef, $class = null, $container = null, $params = [])
    {
        $processor = new \OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor\BaseOperator($nodeDef);
        $processor->setGraphQLService($this->getGraphQlService());

        $typeName = strtolower((string) $nodeDef['attributes']['class']);

        $factories = $this->getGraphQlService()->getDataObjectMutationTypeGeneratorFactories();
        $factory = $factories->get('typegenerator_' . 'mutation' . 'operator_' . $typeName);
        $determinedType = $factory->resolveInputTypeFromNodeDef($nodeDef, $class, $container);

        return [
            'arg' => $determinedType,
            'processor' => $processor->process(...),
        ];
    }
}
