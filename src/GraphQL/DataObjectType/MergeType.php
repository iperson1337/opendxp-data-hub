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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectType;

use Exception;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ClassTypeDefinitions;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition;

/**
 * @deprecated will be removed in Data Hub 2
 */
class MergeType extends UnionType
{
    use ServiceTrait;

    /**
     * @param array $nodeDef
     * @param ClassDefinition|null $class
     * @param object|null $container
     * @param array $config
     */
    public function __construct(
        Service $graphQlService,
        protected $nodeDef,
        protected $class = null,
        protected $container = null,
        $config = []
    ) {
        $this->setGraphQLService($graphQlService);
        parent::__construct($config);
    }

    /**
     * @return ClassDefinition|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param ClassDefinition|null $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    public function getTypes(): array
    {
        $nodeDef = $this->nodeDef;
        $childTypes = [];
        $attributes = $nodeDef['attributes'];
        $fieldHelper = $this->getGraphQlService()->getObjectFieldHelper();

        if ($attributes['children']) {
            foreach ($attributes['children'] as $childDef) {
                $type = $fieldHelper->getGraphQlTypeFromNodeConf($childDef, $this->class, $this->container);
                $childTypes[] = $type;
            }
        }

        $result = [];
        $this->buildChildTypes($childTypes, $result);

        return $result;
    }

    /**
     * @throws Exception
     */
    public function resolveType($element, $context, ResolveInfo $info)
    {
        if ($element) {
            if ($element instanceof DataObject) {
                $concrete = ($element instanceof DataObject\Concrete) ? $element : DataObject\Concrete::getById($element->getId());

                return ClassTypeDefinitions::get($concrete->getClassName());
            }
            if ($element instanceof Asset) {
                return $this->getGraphQlService()->buildAssetType('asset');
            }
        }

        return null;
    }

    /**
     * @param array $childTypes
     * @param array $result
     */
    public function buildChildTypes($childTypes, &$result)
    {
        if (!$childTypes) {
            return;
        }
        // this will always return a list type

        foreach ($childTypes as $childType) {
            if ($childType instanceof ListOfType) {
                $wrappedType = $childType->getWrappedType();
                $this->buildChildTypes([$wrappedType], $result);
            } else {
                if ($childType instanceof UnionType) {
                    $allowedTypes = $childType->getTypes();
                    $this->buildChildTypes($allowedTypes, $result);
                } else {
                    $result[$childType->name] = $childType;
                }
            }
        }
    }
}
