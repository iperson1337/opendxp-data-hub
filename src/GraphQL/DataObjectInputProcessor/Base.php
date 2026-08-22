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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Fieldcollection\Data\AbstractData;

class Base
{
    use ServiceTrait;

    /**
     * @param array $nodeDef
     */
    public function __construct(protected $nodeDef)
    {
    }

    /**
     * @return mixed
     */
    public function getAttribute()
    {
        return $this->nodeDef['attributes']['attribute'];
    }

    /**
     * @param Concrete|AbstractData $object
     * @param mixed $newValue
     * @param array $args
     * @param array $context
     *
     * @throws Exception
     */
    public function process($object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();

        Service::setValue($object, $attribute, fn ($container, $setter) => $container->$setter($newValue));
    }

    /**
     * @param array $nodeDef
     *
     * @return mixed
     */
    public function getParentProcessor($nodeDef, ClassDefinition $class)
    {
        $nodeDefAttributes = $nodeDef['attributes'];
        $children = $nodeDefAttributes['children'];
        if (!$children) {
            return null;
        }

        $firstChild = $children[0];
        $firstChildAttributes = $firstChild['attributes'];
        $service = $this->getGraphQlService();

        $factories = $service->getDataObjectMutationTypeGeneratorFactories();

        if ($firstChild['isOperator']) {
            //  we only support the simple case with one child
            $operatorClass = $firstChildAttributes['class'];
            $typeName = strtolower($operatorClass);
            $mutationConfigGenerator = $factories->get('typegenerator_mutationoperator_' . $typeName);
            $config = $mutationConfigGenerator->getGraphQlMutationOperatorConfig($firstChild, $class);
        } else {
            $typeName = $firstChildAttributes['dataType'];
            $mutationConfigGenerator = $factories->get('typegenerator_dataobjectmutationdatatype_' . $typeName);
            $config = $mutationConfigGenerator->getGraphQlMutationFieldConfig($firstChild, $class);
        }

        $result = $config['processor'];

        return $result;
    }
}
