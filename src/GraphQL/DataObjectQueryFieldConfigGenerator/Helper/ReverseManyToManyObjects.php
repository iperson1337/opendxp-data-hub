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

namespace OpenDxp\Bundle\DataHubBundle\GraphQL\DataObjectQueryFieldConfigGenerator\Helper;

use Exception;
use GraphQL\Type\Definition\ResolveInfo;
use OpenDxp\Bundle\DataHubBundle\GraphQL\BaseDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Service;
use OpenDxp\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use OpenDxp\Bundle\DataHubBundle\WorkspaceHelper;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\ClassDefinition\Data;
use OpenDxp\Model\DataObject\Concrete;

class ReverseManyToManyObjects
{
    use ServiceTrait;

    /**
     * @param string $attribute
     * @param Data $fieldDefinition
     * @param ClassDefinition $class
     */
    public function __construct(
        Service $graphQlService,
        public $attribute,
        public $fieldDefinition,
        public $class
    ) {
        $this->setGraphQLService($graphQlService);
    }

    /**
     * @param BaseDescriptor|null $value
     * @param array $args
     * @param array $context
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function resolve($value = null, $args = [], $context = [], ?ResolveInfo $resolveInfo = null)
    {
        $objectId = $value['id'];
        $object = Concrete::getById($objectId);

        $relations = $object->getRelationData($this->fieldDefinition->getOwnerFieldName(), false, $this->fieldDefinition->getOwnerClassId());
        if ($relations) {
            $result = [];
            foreach ($relations as $relationRaw) {
                $relation = Concrete::getById($relationRaw['id']);
                if ($relation) {
                    if (!WorkspaceHelper::checkPermission($relation, 'read')) {
                        continue;
                    }

                    $data = new ElementDescriptor($relation);
                    $this->getGraphQlService()->extractData($data, $relation, $args, $context, $resolveInfo);
                    $result[] = $data;
                }
            }

            return $result;
        }

        return $relations;
    }
}
